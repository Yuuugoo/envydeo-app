<?php

namespace App\Livewire\Social;

use App\Models\DiaryEntry;
use App\Models\User;
use App\Models\Watchlist;
use App\Notifications\MovieNightInvite;
use App\Services\ExternalScores;
use App\Services\MovieNightRooms;
use App\Services\SocialGraph;
use App\Services\Tmdb;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

/**
 * Social panel on movie/TV pages: external scores + links, friends who want to watch it
 * (with "Start a movie night with them"), and reviews (friends, everyone on Envydeo, TMDB).
 */
#[Lazy]
class TitleSocial extends Component
{
    #[Locked]
    public int $tmdbId;

    #[Locked]
    public string $type = 'movie';

    #[Locked]
    public string $title = '';

    #[Locked]
    public ?string $imdbId = null;

    #[Locked]
    public ?float $tmdbRating = null;

    public string $tab = 'friends';

    public function mount(int $tmdbId, string $type, string $title, ?string $imdbId = null, ?float $tmdbRating = null): void
    {
        $this->tmdbId = $tmdbId;
        $this->type = $type === 'tv' ? 'tv' : 'movie';
        $this->title = $title;
        $this->imdbId = $imdbId;
        $this->tmdbRating = $tmdbRating;
        $this->tab = auth()->check() ? 'friends' : 'everyone';
    }

    #[On('diary-updated')]
    public function refreshReviews(): void {}

    public function placeholder()
    {
        return <<<'HTML'
        <div class="space-y-4" aria-hidden="true">
            <div class="h-24 animate-pulse rounded-3xl bg-ink-900/70"></div>
            <div class="h-40 animate-pulse rounded-3xl bg-ink-900/70"></div>
        </div>
        HTML;
    }

    /** Creates a Movie Night room and invites the people you follow who saved this title. */
    public function startMovieNight(SocialGraph $graph, MovieNightRooms $rooms)
    {
        $viewer = auth()->user();
        if ($this->type !== 'movie') {
            return null; // Movie Night deals movies only
        }
        if (!$viewer?->username) {
            return $this->redirectRoute('login');
        }

        $friends = $this->interestedPeople($graph, $viewer);
        try {
            $room = $rooms->create([], $viewer->name, $viewer);
        } catch (RuntimeException $e) {
            $this->addError('movieNight', $e->getMessage());

            return null;
        }

        Notification::send($friends, new MovieNightInvite($viewer, $room));

        return $this->redirectRoute('movie-night.show', $room->code);
    }

    /** People the viewer follows who have this title (movie or show) on their watchlist. */
    private function interestedPeople(SocialGraph $graph, User $viewer)
    {
        $ids = array_diff($graph->followingIds($viewer), $graph->hiddenIds($viewer));

        return User::whereIn('id', Watchlist::whereIn('user_id', $ids)->where('tmdb_id', $this->tmdbId)->where('media_type', $this->type)->pluck('user_id'))
            ->whereNotNull('username')->get();
    }

    public function render(SocialGraph $graph, ExternalScores $scores, Tmdb $tmdb)
    {
        $viewer = auth()->user();
        $base = DiaryEntry::with('user')->where('tmdb_id', $this->tmdbId)->where('media_type', $this->type);

        $friendIds = $viewer ? array_diff($graph->followingIds($viewer), $graph->hiddenIds($viewer)) : [];
        // Your own log sits alongside the people you follow.
        $friendEntries = $viewer ? (clone $base)->whereIn('user_id', [...$friendIds, $viewer->id])->latest()->limit(20)->get() : collect();

        $hidden = $viewer ? $graph->hiddenIds($viewer) : [];
        $everyone = (clone $base)->whereNotNull('review')
            ->whereHas('user', fn ($q) => $q->where('is_private', false)->whereNotNull('username'))
            ->whereNotIn('user_id', $hidden)
            ->withCount('likes')->orderByDesc('likes_count')->latest()->limit(20)->get();

        // Envydeo verdicts from public profiles: count per verdict → "% recommend" + a breakdown bar.
        $verdicts = (clone $base)->whereNotNull('verdict')->whereHas('user', fn ($q) => $q->where('is_private', false))
            ->toBase()->selectRaw('verdict, count(*) as n')->groupBy('verdict')->pluck('n', 'verdict')->map(fn ($n) => (int) $n);
        $verdictTotal = $verdicts->sum();
        $tmdbReviews = $this->tab === 'tmdb'
            ? collect($tmdb->get("/{$this->type}/{$this->tmdbId}/reviews", [], Tmdb::DAY)['results'] ?? [])->take(8)
            : collect();

        return view('livewire.social.title-social', [
            'scores' => $this->type === 'movie' ? $scores->forImdbId($this->imdbId) : null,
            'scoresEnabled' => $scores->enabled(),
            'interested' => $viewer ? $this->interestedPeople($graph, $viewer) : collect(),
            'friendEntries' => $friendEntries,
            'friendVerdicts' => $friendEntries->where('user_id', '!=', $viewer?->id)->whereNotNull('verdict')->unique('user_id'),
            'everyone' => $everyone,
            'verdicts' => $verdicts,
            'verdictTotal' => $verdictTotal,
            'recommendPct' => $verdictTotal ? (int) round(100 * $verdicts->only(DiaryEntry::RECOMMEND)->sum() / $verdictTotal) : null,
            'bestWithFriends' => (clone $base)->where('best_with_friends', true)->count(),
            'tmdbReviews' => $tmdbReviews,
            'links' => array_filter([
                'IMDb' => $this->imdbId ? "https://www.imdb.com/title/{$this->imdbId}/" : null,
                'Letterboxd' => $this->type === 'movie' ? "https://letterboxd.com/tmdb/{$this->tmdbId}/" : null,
                'Rotten Tomatoes' => 'https://www.rottentomatoes.com/search?search=' . rawurlencode($this->title),
            ]),
        ]);
    }
}
