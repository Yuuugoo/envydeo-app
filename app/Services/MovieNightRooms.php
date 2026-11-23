<?php

namespace App\Services;

use App\Models\MatchRoom;
use App\Models\MovieNightEvent;
use App\Models\User;
use App\Models\Watchlist;
use RuntimeException;

/**
 * Creates Movie Night rooms and deals their decks. Shared by the create form,
 * "Decide for me", themed decks, Watchlist Showdown and "Us" rooms.
 */
class MovieNightRooms
{
    public function __construct(private MovieNightDealer $dealer) {}

    /**
     * @param  array{filters?: array, theme?: ?string, source?: string, persistent?: bool, start?: bool}  $options
     *
     * @throws RuntimeException with a user-facing message when no deck can be dealt
     */
    public function create(array $options, string $hostName, ?User $user): MatchRoom
    {
        $source = $options['source'] ?? 'discover';
        $theme = $options['theme'] ?? null;
        $filters = ($options['filters'] ?? []) + ['region' => 'US', 'genres' => [], 'providers' => []];
        $filters['theme'] = $theme;
        $persistent = (bool) ($options['persistent'] ?? false);
        $start = (bool) ($options['start'] ?? false);

        if ($source === 'watchlist') {
            if (!$user) {
                throw new RuntimeException('Sign in to use your watchlist.');
            }
            $deck = $this->dealer->watchlistDeck($this->watchlistCounts([$user->getKey()]));
            if (!$deck) {
                throw new RuntimeException('Your watchlist is empty. Add a few movies first.');
            }
        } else {
            $deck = $this->dealer->deal($filters, range(1, MovieNightDealer::PAGES_PER_DEAL));
            if (!$deck) {
                throw new RuntimeException('No movies match those filters. Try fewer genres or services, or a lower rating.');
            }
        }

        $room = MatchRoom::create([
            'code' => MatchRoom::newCode(),
            'status' => $start ? 'voting' : 'lobby',
            'source' => $source,
            'theme' => $theme,
            'is_persistent' => $persistent,
            'filters' => $filters,
            'candidates' => $deck,
            'next_page' => MovieNightDealer::PAGES_PER_DEAL + 1,
            'started_at' => $start ? now() : null,
            'expires_at' => $persistent ? null : now()->addHours(MatchRoom::LIFETIME_HOURS),
        ]);

        [, $token] = $room->addParticipant($hostName, isHost: true, userId: $user?->getKey());
        session()->put(MatchRoom::sessionKey($room->id), $token);

        $kind = $persistent ? 'us' : ($start ? 'solo' : 'group');
        MovieNightEvent::record('room_created', $room, array_filter(['kind' => $kind, 'theme' => $theme, 'source' => $source]));
        if ($start) {
            MovieNightEvent::record('started', $room, ['participants' => 1]);
        }

        return $room;
    }

    /** More cards for a room (discover rooms only; watchlist decks are finite). Returns how many were added. */
    public function dealMore(MatchRoom $room): int
    {
        if ($room->source === 'watchlist') {
            $more = $this->dealer->watchlistDeck($this->watchlistCounts($this->participantUserIds($room)), $room->candidateIds());
        } else {
            $pages = range($room->next_page, $room->next_page + MovieNightDealer::PAGES_PER_DEAL - 1);
            $more = $this->dealer->deal($room->filters, $pages, $room->candidateIds());
        }

        if (!$more) {
            return 0;
        }

        $room->update([
            'candidates' => array_merge($room->candidates, $more),
            'next_page' => $room->source === 'watchlist' ? $room->next_page : $room->next_page + MovieNightDealer::PAGES_PER_DEAL,
        ]);

        return count($more);
    }

    /** Watchlist Showdown: rebuild the deck from everyone in the room (called when the host starts). */
    public function refreshWatchlistDeck(MatchRoom $room): void
    {
        $deck = $this->dealer->watchlistDeck($this->watchlistCounts($this->participantUserIds($room)));
        if ($deck) {
            $room->update(['candidates' => $deck]);
        }
    }

    /** Fresh deck for "another round" (keeps people and filters). */
    public function freshDeck(MatchRoom $room, array $exclude = []): array
    {
        return $room->source === 'watchlist'
            ? $this->dealer->watchlistDeck($this->watchlistCounts($this->participantUserIds($room)), $exclude)
            : $this->dealer->deal($room->filters, range(1, MovieNightDealer::PAGES_PER_DEAL), $exclude);
    }

    /** @return array<int, int> tmdb_id => number of these users who saved it */
    private function watchlistCounts(array $userIds): array
    {
        return Watchlist::whereIn('user_id', $userIds)
            ->selectRaw('tmdb_id, count(*) as saves')
            ->groupBy('tmdb_id')
            ->pluck('saves', 'tmdb_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    private function participantUserIds(MatchRoom $room): array
    {
        return $room->participants()->whereNotNull('user_id')->pluck('user_id')->all();
    }
}
