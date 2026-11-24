<?php

namespace App\Support;

use App\Services\Tmdb;

/**
 * Compact copy of a movie/show stored with check-ins, diary entries and Top 4 picks,
 * so feeds and profiles render without calling TMDB for every item.
 */
class TitleSnapshot
{
    public static function from(array $item, string $type = 'movie'): array
    {
        $date = $item['release_date'] ?? $item['first_air_date'] ?? null;

        return [
            'id' => (int) $item['id'],
            'type' => $type === 'tv' ? 'tv' : 'movie',
            'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
            'poster_path' => $item['poster_path'] ?? null,
            'backdrop_path' => $item['backdrop_path'] ?? null,
            'year' => $date ? substr($date, 0, 4) : null,
        ];
    }

    /** Fetch one title from TMDB (cached) and snapshot it; null if it doesn't exist. */
    public static function fetch(int $tmdbId, string $type = 'movie'): ?array
    {
        $type = $type === 'tv' ? 'tv' : 'movie';
        $item = app(Tmdb::class)->get("/{$type}/{$tmdbId}", [], Tmdb::DAY);

        return empty($item['id']) ? null : self::from($item, $type);
    }

    /** Title search for pickers (check-in composer, Top 4). */
    public static function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $results = app(Tmdb::class)->get('/search/multi', ['query' => $query, 'include_adult' => 'false'], Tmdb::MINUTES_10)['results'] ?? [];

        return collect($results)
            ->filter(fn ($r) => in_array($r['media_type'] ?? null, ['movie', 'tv'], true) && !empty($r['poster_path']))
            ->sortByDesc('popularity')
            ->take($limit)
            ->map(fn ($r) => self::from($r, $r['media_type']))
            ->values()
            ->all();
    }

    public static function url(array $snapshot): string
    {
        return route(($snapshot['type'] ?? 'movie') === 'tv' ? 'show.show' : 'movie.show', $snapshot['id']);
    }
}
