<?php

namespace App\Livewire\Social;

use App\Models\MatchParticipant;
use App\Models\User;
use App\Models\Watchlist;
use App\Notifications\MovieNightInvite;
use App\Services\Feed;
use App\Services\MovieNightRooms;
use App\Services\SocialGraph;
use App\Services\Tmdb;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use RuntimeException;

/**
 * Signed-in home ("Tonight"): friends' live check-ins, movies you and people you follow
 * both want to watch (one tap starts a Watchlist Showdown with them), and your open
 * Movie Night rooms, and a short "lately" slice of the friends feed.
 */
class TonightHome extends Component
{
    /** Start a Watchlist Showdown room and invite the people who also saved this movie. */
    public function startNight(int $tmdbId, SocialGraph $graph, MovieNightRooms $rooms)
    {
        $viewer = auth()->user();
        abort_unless($viewer?->username, 403);

        $people = $this->overlapPeople($viewer, $graph, $tmdbId);
        if ($people->isEmpty()) {
            return null;
        }
        if (RateLimiter::tooManyAttempts('tonight-night:' . $viewer->id, 10)) {
            $this->addError('night', 'Slow down a little: try again in a few minutes.');

            return null;
        }
        RateLimiter::hit('tonight-night:' . $viewer->id, 3600);

        try {
            $room = $rooms->create(['source' => 'watchlist'], $viewer->name, $viewer);
        } catch (RuntimeException $e) {
            $this->addError('night', $e->getMessage());

            return null;
        }

        Notification::send($people, new MovieNightInvite($viewer, $room));

        return $this->redirectRoute('movie-night.show', $room->code);
    }

    /** People you follow (not hidden) who also have movie $tmdbId on their watchlist. */
    private function overlapPeople(User $viewer, SocialGraph $graph, int $tmdbId): Collection
    {
        if (!Watchlist::movies()->where('user_id', $viewer->id)->where('tmdb_id', $tmdbId)->exists()) {
            return collect();
        }
        $ids = array_diff($graph->followingIds($viewer), $graph->hiddenIds($viewer));

        return User::whereIn('id', Watchlist::movies()->whereIn('user_id', $ids)->where('tmdb_id', $tmdbId)->pluck('user_id'))
            ->whereNotNull('username')->get();
    }

    /**
     * Movies and shows on your watchlist that people you follow also saved, most shared first.
     * [['movie' => tmdb detail, 'type' => movie|tv, 'people' => Collection<User>], ...]
     */
    private function overlaps(User $viewer, SocialGraph $graph, Tmdb $tmdb): Collection
    {
        $ids = array_diff($graph->followingIds($viewer), $graph->hiddenIds($viewer));
        if (!$ids) {
            return collect();
        }

        $mine = Watchlist::where('user_id', $viewer->id)->get(['tmdb_id', 'media_type'])
            ->map(fn ($w) => $w->media_type . '-' . $w->tmdb_id)->flip();
        $rows = Watchlist::whereIn('user_id', $ids)
            ->whereIn('tmdb_id', Watchlist::where('user_id', $viewer->id)->select('tmdb_id'))
            ->get(['user_id', 'tmdb_id', 'media_type', 'created_at'])
            ->filter(fn ($w) => isset($mine[$w->media_type . '-' . $w->tmdb_id]));
        if ($rows->isEmpty()) {
            return collect();
        }

        $top = $rows->groupBy(fn ($w) => $w->media_type . '-' . $w->tmdb_id)
            ->sortByDesc(fn ($g) => [$g->count(), $g->max('created_at')])
            ->take(3);

        $users = User::whereIn('id', $top->flatten()->pluck('user_id')->unique())->whereNotNull('username')->get()->keyBy('id');
        $details = $tmdb->many($top->map(fn ($g) => [$g->first()->tmdbPath()])->all(), Tmdb::DAY);

        return $top->map(fn ($g, $key) => [
            'movie' => $details[$key] ?? [],
            'type' => $g->first()->media_type,
            'people' => $g->pluck('user_id')->unique()->map(fn ($uid) => $users[$uid] ?? null)->filter()->values(),
        ])->filter(fn ($o) => !empty($o['movie']['id']) && $o['people']->isNotEmpty())->values();
    }

    public function render(Feed $feed, SocialGraph $graph, Tmdb $tmdb)
    {
        $viewer = auth()->user();

        $rooms = MatchParticipant::where('user_id', $viewer->id)
            ->whereHas('room', fn ($q) => $q->active()->where(fn ($q) => $q->where('status', '!=', 'matched')->orWhere('is_persistent', true)))
            ->with(['room' => fn ($q) => $q->withCount('participants')])
            ->latest()->limit(4)->get()
            ->pluck('room')->unique('id')->values();

        return view('livewire.social.tonight-home', [
            'viewer' => $viewer,
            'groups' => $feed->tonight($viewer),
            'overlaps' => $this->overlaps($viewer, $graph, $tmdb),
            'rooms' => $rooms,
            'parties' => \App\Models\WatchParty::active()
                ->whereHas('members', fn ($q) => $q->where('user_id', $viewer->id))
                ->withCount('members')->latest()->limit(4)->get(),
            'lately' => $feed->activities($viewer, 12)->reject(fn ($a) => $a->type === 'checked_in')->take(5)->values(),
            'friendCount' => count($graph->friendIds($viewer)),
        ]);
    }
}
