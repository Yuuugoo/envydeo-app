<?php

namespace App\Livewire\MovieNight;

use App\Models\MatchParticipant;
use App\Models\MatchResult;
use App\Models\MatchRoom;
use App\Models\MatchVote;
use App\Models\MovieNightEvent;
use App\Services\MovieNightDealer;
use App\Services\MovieNightRooms;
use App\Services\Tmdb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * One Movie Night room. Everyone polls every 2s for lobby/progress/match updates;
 * swiping happens client-side (Alpine) and each decision is sent with vote().
 *
 * A movie is a match as soon as every participant in the room has liked it.
 * Regular rooms end at the first match; "Us" rooms (is_persistent) keep collecting
 * matches into "Our matches" and refill the deck automatically.
 */
class Room extends Component
{
    private const REFILL_BELOW = 5; // "Us" rooms: deal more when someone has fewer cards left than this

    #[Locked]
    public int $roomId;

    public string $joinName = '';

    public string $tab = 'swipe'; // "Us" rooms: swipe | matches

    public function mount(MatchRoom $room): void
    {
        $this->roomId = $room->id;
        $this->joinName = (string) (auth()->user()?->name ?? '');
    }

    // ---------------------------------------------------------------- actions

    public function join(): void
    {
        $room = $this->room();
        if ($this->me($room)) {
            return;
        }
        if ($room->is_persistent && !auth()->check()) {
            $this->addError('joinName', 'Sign in to join an “Us” room, so it remembers you on every device.');

            return;
        }

        $this->validate(['joinName' => ['required', 'string', 'max:30']], [], ['joinName' => 'your name']);

        if ($room->participants()->count() >= $room->maxParticipants()) {
            $this->addError('joinName', 'This room is full.');

            return;
        }

        [, $token] = $room->addParticipant($this->joinName, userId: auth()->id());
        session()->put(MatchRoom::sessionKey($room->id), $token);
        MovieNightEvent::record('joined', $room, ['status' => $room->status]);
    }

    public function start(MovieNightRooms $rooms): void
    {
        $room = $this->room();
        if (!$this->me($room)?->is_host || $room->status !== 'lobby') {
            return;
        }

        if ($room->source === 'watchlist') {
            $rooms->refreshWatchlistDeck($room); // include everyone who joined
        }

        $room->update(['status' => 'voting', 'started_at' => now()]);
        MovieNightEvent::record('started', $room, ['participants' => $room->participants()->count()]);
    }

    /** Called from the swipe deck. Renderless so swiping stays instant; polling picks up changes. */
    #[Renderless]
    public function vote(int $tmdbId, bool $liked, MovieNightRooms $rooms): array
    {
        $room = $this->room();
        $me = $this->me($room);

        if (!$me || $room->status !== 'voting' || !in_array($tmdbId, $room->candidateIds(), true)) {
            return ['matched' => $room->status === 'matched', 'refresh' => false];
        }

        MatchVote::updateOrCreate(
            ['match_participant_id' => $me->id, 'tmdb_id' => $tmdbId],
            ['match_room_id' => $room->id, 'liked' => $liked],
        );

        $matched = $liked && $this->checkForMatch($room, $tmdbId);

        // "Us" rooms never run dry: top the deck up when this person is close to the end.
        $refilled = false;
        if ($room->is_persistent && $this->remainingFor($room, $me) < self::REFILL_BELOW) {
            $refilled = $rooms->dealMore($room) > 0;
        }

        return [
            'matched' => $matched,
            'title' => $matched ? ($room->candidate($tmdbId)['title'] ?? null) : null,
            'refresh' => $matched || $refilled,
        ];
    }

    /** Host settles it when the deck ran out without a unanimous like. */
    public function pick(int $tmdbId): void
    {
        $room = $this->room();
        if ($this->me($room)?->is_host && !$room->is_persistent && $room->status !== 'matched' && $room->candidate($tmdbId)) {
            $this->recordResult($room, $tmdbId);
            $room->update(['status' => 'matched', 'matched_tmdb_id' => $tmdbId]);
            MovieNightEvent::record('matched', $room, ['how' => 'host_pick'] + $this->matchMeta($room));
        }
    }

    /** Host adds fresh cards to the deck. */
    public function dealMore(MovieNightRooms $rooms): void
    {
        $room = $this->room();
        if (!$this->me($room)?->is_host || $room->status !== 'voting') {
            return;
        }

        if ($rooms->dealMore($room) === 0) {
            $this->addError('deck', $room->source === 'watchlist'
                ? 'That’s every movie on your watchlists. Pick from the closest matches below.'
                : 'That’s every movie matching your filters. Pick from the closest matches below.');
        }
    }

    /** Host starts over with a freshly shuffled deck (keeps the same people and filters). */
    public function newRound(MovieNightRooms $rooms): void
    {
        $room = $this->room();
        if (!$this->me($room)?->is_host || $room->is_persistent) {
            return;
        }

        $deck = $rooms->freshDeck($room, array_filter([$room->matched_tmdb_id]));

        DB::transaction(function () use ($room, $deck) {
            $room->votes()->delete();
            $room->update([
                'status' => 'voting',
                'matched_tmdb_id' => null,
                'candidates' => $deck ?: $room->candidates,
                'next_page' => MovieNightDealer::PAGES_PER_DEAL + 1,
                'started_at' => now(),
            ]);
        });
        MovieNightEvent::record('started', $room, ['participants' => $room->participants()->count(), 'round' => 'again']);
    }

    /** "Us" rooms: tick a match off once you've watched it (or untick). */
    public function toggleWatched(int $resultId): void
    {
        $room = $this->room();
        if (!$room->is_persistent || !$this->me($room)) {
            return;
        }

        $result = $room->results()->findOrFail($resultId);
        $result->update(['watched_at' => $result->watched_at ? null : now()]);
    }

    /** Social: invite a mutual friend straight into this room (notification + push). Once per friend per hour. */
    public function inviteFriend(string $userId, \App\Services\SocialGraph $graph): void
    {
        $room = $this->room();
        $viewer = auth()->user();
        if (!$viewer || !$this->me($room) || !$graph->areFriends($viewer, $friend = \App\Models\User::findOrFail($userId))) {
            return;
        }

        if (\Illuminate\Support\Facades\Cache::add("mn-invite:{$room->id}:{$friend->id}", true, 3600)) {
            $friend->notify(new \App\Notifications\MovieNightInvite($viewer, $room));
        }
        $this->invited[] = $friend->id;
    }

    /** @var list<string> friends invited during this visit (for the button state) */
    public array $invited = [];

    /** Anonymous funnel stats for invites and share cards. */
    #[Renderless]
    public function trackShare(string $what, string $channel): void
    {
        $room = $this->room();
        if ($this->me($room) && in_array($what, ['invite', 'card'], true) && in_array($channel, ['copy', 'native', 'download', 'qr'], true)) {
            MovieNightEvent::record($what === 'invite' ? 'invite_shared' : 'card_shared', $room, ['channel' => $channel]);
        }
    }

    // ---------------------------------------------------------------- render

    public function render()
    {
        $room = $this->room();
        $me = $this->me($room);
        $participants = $room->participants()->withCount('votes')->orderBy('id')->get();
        $deckSize = count($room->candidates);
        $status = $room->status;

        // Everyone has swiped every card and nobody matched: show the closest picks.
        if ($status === 'voting' && $participants->isNotEmpty() && $participants->every(fn ($p) => $p->votes_count >= $deckSize)) {
            $status = 'exhausted';
        }

        $myVotedIds = $me ? $me->votes()->pluck('tmdb_id')->map(fn ($id) => (int) $id)->all() : [];
        $results = $room->is_persistent ? $room->results()->latest()->get() : collect();

        return view('livewire.movie-night.room', [
            'room' => $room,
            'me' => $me,
            'status' => $status,
            'participants' => $participants,
            'deckSize' => $deckSize,
            'myDeck' => array_values(array_filter($room->candidates, fn ($c) => !in_array($c['id'], $myVotedIds, true))),
            'genres' => app(Tmdb::class)->movieGenres(),
            'theme' => $room->theme(),
            'match' => $status === 'matched' ? $room->candidate($room->matched_tmdb_id) : null,
            'whereToWatch' => $status === 'matched' && $room->matched_tmdb_id
                ? app(MovieNightDealer::class)->whereToWatch($room->matched_tmdb_id, $room->filters['region'] ?? 'US')
                : null,
            'topPicks' => $status === 'exhausted' ? $this->topPicks($room) : collect(),
            'results' => $results,
            'shareUrl' => route('movie-night.show', $room->code),
            'qrSvg' => $status === 'lobby' || $room->is_persistent ? $this->qrSvg(route('movie-night.show', $room->code)) : null,
            'regionName' => MovieNightDealer::REGIONS[$room->filters['region'] ?? 'US'] ?? null,
            'invitable' => $this->invitableFriends($room, $participants),
        ]);
    }

    /** Mutual friends of the signed-in viewer who aren't in the room yet (lobby / "Us" waiting). */
    private function invitableFriends(MatchRoom $room, $participants)
    {
        $viewer = auth()->user();
        if (!$viewer?->username || $participants->count() >= $room->maxParticipants() || $room->status === 'matched') {
            return collect();
        }
        $graph = app(\App\Services\SocialGraph::class);
        $inRoom = $participants->pluck('user_id')->filter()->all();

        return \App\Models\User::whereIn('id', array_diff($graph->friendIds($viewer), $graph->hiddenIds($viewer), $inRoom))
            ->orderBy('name')->limit(12)->get();
    }

    // ---------------------------------------------------------------- helpers

    private function room(): MatchRoom
    {
        return MatchRoom::active()->findOrFail($this->roomId);
    }

    private function me(MatchRoom $room): ?MatchParticipant
    {
        return $room->participantFor(session(MatchRoom::sessionKey($room->id)), auth()->user());
    }

    private function remainingFor(MatchRoom $room, MatchParticipant $me): int
    {
        return count($room->candidates) - $me->votes()->count();
    }

    /** A match happens when every participant has liked the same movie. */
    private function checkForMatch(MatchRoom $room, int $tmdbId): bool
    {
        $needed = $room->participants()->count();
        if ($room->is_persistent) {
            // "Us" rooms are for two: the creator can swipe ahead, matches appear once the partner agrees.
            $needed = max(MatchRoom::MAX_US_PARTICIPANTS, $needed);
        }
        $likes = $room->votes()->where('tmdb_id', $tmdbId)->where('liked', true)->count();

        if ($likes < $needed) {
            return false;
        }

        if ($room->is_persistent) {
            $isNew = $this->recordResult($room, $tmdbId);
            if ($isNew) {
                MovieNightEvent::record('matched', $room, ['how' => 'unanimous', 'kind' => 'us', 'participants' => $needed]);
            }

            return $isNew;
        }

        // Conditional update so two simultaneous final likes can't both "win".
        $won = MatchRoom::whereKey($room->id)->where('status', 'voting')
            ->update(['status' => 'matched', 'matched_tmdb_id' => $tmdbId]) === 1;

        if ($won) {
            $this->recordResult($room, $tmdbId);
            MovieNightEvent::record('matched', $room, ['how' => 'unanimous'] + $this->matchMeta($room));
        }

        return true;
    }

    /** Saves the match; returns true if it wasn't already recorded. */
    private function recordResult(MatchRoom $room, int $tmdbId): bool
    {
        $card = $room->candidate($tmdbId) ?? ['id' => $tmdbId];
        $isNew = MatchResult::firstOrCreate(
            ['match_room_id' => $room->id, 'tmdb_id' => $tmdbId],
            ['movie' => $card],
        )->wasRecentlyCreated;

        // Social feed: "Ana matched on Dune at Movie Night" for each signed-in participant with a profile.
        if ($isNew) {
            $movie = ['id' => $tmdbId, 'type' => 'movie', 'title' => $card['title'] ?? 'a movie', 'poster_path' => $card['poster_path'] ?? null, 'backdrop_path' => $card['backdrop_path'] ?? null, 'year' => $card['year'] ?? null];
            \App\Models\User::whereIn('id', $room->participants()->whereNotNull('user_id')->pluck('user_id'))
                ->whereNotNull('username')->get()
                ->each(fn ($user) => \App\Models\Activity::record($user, 'matched', null, ['movie' => $movie]));
        }

        return $isNew;
    }

    private function matchMeta(MatchRoom $room): array
    {
        $participants = $room->participants()->count();

        return [
            'kind' => $participants === 1 ? 'solo' : 'group',
            'participants' => $participants,
            'seconds' => $room->started_at ? (int) $room->started_at->diffInSeconds(now()) : null,
            'theme' => $room->theme,
        ];
    }

    private function topPicks(MatchRoom $room)
    {
        return $room->votes()
            ->where('liked', true)
            ->select('tmdb_id', DB::raw('count(*) as likes'))
            ->groupBy('tmdb_id')
            ->orderByDesc('likes')
            ->limit(3)
            ->get()
            ->map(fn ($row) => ['movie' => $room->candidate((int) $row->tmdb_id), 'likes' => (int) $row->likes])
            ->filter(fn ($pick) => $pick['movie'] !== null)
            ->values();
    }

    private function qrSvg(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd()));

        // Strip the XML prolog so the SVG can be inlined in HTML.
        return trim(preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($url)));
    }
}
