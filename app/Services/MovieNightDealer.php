<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Builds Movie Night decks from TMDB discover and looks up streaming availability.
 * Streaming availability data on TMDB is provided by JustWatch (attribution required).
 */
class MovieNightDealer
{
    public const DECK_SIZE = 30;

    public const PAGES_PER_DEAL = 3;

    public const REGIONS = [
        'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'AU' => 'Australia',
        'PH' => 'Philippines', 'SG' => 'Singapore', 'IN' => 'India', 'JP' => 'Japan', 'KR' => 'South Korea',
        'DE' => 'Germany', 'FR' => 'France', 'ES' => 'Spain', 'IT' => 'Italy', 'NL' => 'Netherlands',
        'BR' => 'Brazil', 'MX' => 'Mexico',
    ];

    public const RUNTIMES = [90 => 'Under 1h 30m', 120 => 'Under 2h', 150 => 'Under 2h 30m'];

    public const RATINGS = [6 => '6+', 7 => '7+', 8 => '8+'];

    public function __construct(private Tmdb $tmdb) {}

    /**
     * Deal a shuffled deck of movies matching the room filters.
     *
     * @param  array{genres?: int[], max_runtime?: ?int, min_rating?: ?float, region?: string, providers?: int[]}  $filters
     * @param  int[]  $pages    TMDB discover pages to draw from (fetched in parallel)
     * @param  int[]  $exclude  TMDB ids already in the deck
     * @return list<array>
     */
    public function deal(array $filters, array $pages, array $exclude = []): array
    {
        $query = $this->discoverQuery($filters);

        $responses = $this->tmdb->many(
            collect($pages)->mapWithKeys(fn ($page) => [$page => ['/discover/movie', $query + ['page' => $page]]])->all(),
            Tmdb::HOURS_6
        );

        return collect($responses)
            ->flatMap(fn ($response) => $response['results'] ?? [])
            ->filter(fn ($m) => !empty($m['poster_path']) && !in_array($m['id'], $exclude, true))
            ->unique('id')
            ->shuffle()
            ->take(self::DECK_SIZE)
            ->map(fn ($m) => $this->card($m))
            ->values()
            ->all();
    }

    /**
     * Watchlist Showdown: a deck built only from the given watchlist entries.
     * Titles saved by more people come first (they're the likeliest matches).
     *
     * @param  array<int, int>  $counts  tmdb_id => how many participants saved it
     * @return list<array>
     */
    public function watchlistDeck(array $counts, array $exclude = []): array
    {
        $ids = collect($counts)
            ->reject(fn ($count, $id) => in_array((int) $id, $exclude, true))
            ->map(fn ($count) => $count + mt_rand(0, 99) / 100) // shuffle within each popularity tier
            ->sortDesc()
            ->keys()
            ->take(60);

        $details = $this->tmdb->many(
            $ids->mapWithKeys(fn ($id) => [$id => ["/movie/{$id}"]])->all(),
            Tmdb::DAY
        );

        return $ids
            ->map(fn ($id) => $details[$id] ?? [])
            ->filter(fn ($m) => !empty($m['id']) && !empty($m['poster_path']))
            ->map(fn ($m) => $this->card($m))
            ->values()
            ->all();
    }

    /** Compact card stored in the room's deck (works for discover results and full movie details). */
    private function card(array $m): array
    {
        return [
            'id' => (int) $m['id'],
            'title' => $m['title'] ?? $m['original_title'] ?? 'Untitled',
            'poster_path' => $m['poster_path'],
            'backdrop_path' => $m['backdrop_path'] ?? null,
            'year' => !empty($m['release_date']) ? substr($m['release_date'], 0, 4) : null,
            'rating' => isset($m['vote_average']) ? round((float) $m['vote_average'], 1) : null,
            'overview' => \Illuminate\Support\Str::limit($m['overview'] ?? '', 280),
            'genre_ids' => array_map('intval', $m['genre_ids'] ?? collect($m['genres'] ?? [])->pluck('id')->all()),
        ];
    }

    /** Streaming services available in a region, most popular first. */
    public function providers(string $region, int $limit = 18): Collection
    {
        $response = $this->tmdb->get('/watch/providers/movie', ['watch_region' => $region], Tmdb::WEEK);

        return collect($response['results'] ?? [])
            ->sortBy(fn ($p) => $p['display_priorities'][$region] ?? $p['display_priority'] ?? 999)
            ->take($limit)
            ->map(fn ($p) => ['id' => (int) $p['provider_id'], 'name' => $p['provider_name'], 'logo' => $p['logo_path'] ?? null])
            ->values();
    }

    /**
     * Where a movie can be watched in a region.
     *
     * @return array{link: ?string, stream: list<array>, rent: list<array>, buy: list<array>}
     */
    public function whereToWatch(int $tmdbId, string $region, string $type = 'movie'): array
    {
        $type = $type === 'tv' ? 'tv' : 'movie';
        $data = $this->tmdb->get("/{$type}/{$tmdbId}/watch/providers", [], Tmdb::HOURS_6)['results'][$region] ?? [];

        $map = fn ($list) => collect($list ?? [])
            ->map(fn ($p) => ['name' => $p['provider_name'], 'logo' => $p['logo_path'] ?? null])
            ->values()->all();

        return [
            'link' => $data['link'] ?? null,
            'stream' => $map($data['flatrate'] ?? null),
            'rent' => $map($data['rent'] ?? null),
            'buy' => $map($data['buy'] ?? null),
        ];
    }

    /** Guess a sensible default region from the browser language (e.g. en-PH → PH). */
    public static function regionFromLocale(?string $locale): string
    {
        $country = strtoupper((string) preg_replace('/^[a-z]{2,3}[_-]/i', '', (string) $locale));

        return array_key_exists($country, self::REGIONS) ? $country : 'US';
    }

    private function discoverQuery(array $filters): array
    {
        $providers = $filters['providers'] ?? [];

        // Base defaults, then the theme preset, then anything the host picked explicitly.
        $base = [
            'sort_by' => 'popularity.desc',
            'include_adult' => 'false',
            'language' => 'en-US',
            'vote_count.gte' => 150,
        ];
        $theme = \App\Support\MovieNightThemes::get($filters['theme'] ?? null)['params'] ?? [];

        // Only the host's non-empty choices override the theme preset.
        $explicit = array_filter([
            'vote_average.gte' => $filters['min_rating'] ?? null,
            'with_runtime.lte' => $filters['max_runtime'] ?? null,
            'with_genres' => !empty($filters['genres']) ? implode('|', $filters['genres']) : null, // any of
            'watch_region' => $providers ? ($filters['region'] ?? 'US') : null,
            'with_watch_providers' => $providers ? implode('|', $providers) : null,          // any of
            'with_watch_monetization_types' => $providers ? 'flatrate' : null,                // included in subscription
        ], fn ($v) => $v !== null && $v !== '');

        return array_merge($base, $theme, $explicit);
    }
}
