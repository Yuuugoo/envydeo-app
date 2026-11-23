<?php

namespace App\Support;

/**
 * One-tap themed decks. Each theme is a preset of TMDB /discover/movie parameters.
 * Only keys listed here can be used, so visitors can't inject arbitrary query params.
 *
 * TMDB movie genre ids: 28 Action, 12 Adventure, 16 Animation, 35 Comedy, 80 Crime,
 * 18 Drama, 10751 Family, 14 Fantasy, 27 Horror, 9648 Mystery, 10749 Romance,
 * 878 Science Fiction, 53 Thriller.
 */
class MovieNightThemes
{
    public const THEMES = [
        'date-night' => [
            'name' => 'Date Night',
            'tagline' => 'Romance, rom-coms and swoon-worthy picks',
            'icon' => 'heart',
            'accent' => 'from-rose-500/30',
            'params' => ['with_genres' => '10749|35', 'vote_average.gte' => 6.5, 'with_runtime.lte' => 135],
        ],
        'family-night' => [
            'name' => 'Family Night',
            'tagline' => 'Kid-friendly (rated G or PG)',
            'icon' => 'users',
            'accent' => 'from-sky-500/30',
            'params' => ['with_genres' => '10751|16', 'certification_country' => 'US', 'certification.lte' => 'PG', 'vote_average.gte' => 6.5],
        ],
        'quick-watch' => [
            'name' => 'Under 90 Minutes',
            'tagline' => 'Short, sweet and done before bedtime',
            'icon' => 'film',
            'accent' => 'from-amber-500/30',
            'params' => ['with_runtime.lte' => 90, 'with_runtime.gte' => 60, 'vote_average.gte' => 6.3],
        ],
        'spooky' => [
            'name' => 'Spooky Night',
            'tagline' => 'Horror and thrillers to watch with the lights off',
            'icon' => 'sparkles',
            'accent' => 'from-orange-600/30',
            'params' => ['with_genres' => '27', 'vote_average.gte' => 6.0],
        ],
        'feel-good' => [
            'name' => 'Feel-Good',
            'tagline' => 'Comfort comedies and crowd-warmers',
            'icon' => 'heart-solid',
            'accent' => 'from-yellow-400/25',
            'params' => ['with_genres' => '35|10751', 'without_genres' => '27,53,80', 'vote_average.gte' => 6.8],
        ],
        'mind-benders' => [
            'name' => 'Mind-Benders',
            'tagline' => 'Twisty sci-fi, mysteries and thrillers',
            'icon' => 'sparkles',
            'accent' => 'from-violet-500/30',
            'params' => ['with_genres' => '878|9648|53', 'vote_average.gte' => 7.0, 'vote_count.gte' => 1000],
        ],
        'hidden-gems' => [
            'name' => 'Hidden Gems',
            'tagline' => 'Loved by those who found them, less hyped',
            'icon' => 'star-solid',
            'accent' => 'from-emerald-500/30',
            'params' => ['vote_average.gte' => 7.2, 'vote_count.gte' => 200, 'vote_count.lte' => 2500, 'sort_by' => 'vote_average.desc'],
        ],
        'crowd-pleasers' => [
            'name' => 'Crowd-Pleasers',
            'tagline' => 'Big, popular, easy-to-agree-on hits',
            'icon' => 'users',
            'accent' => 'from-brand-500/30',
            'params' => ['vote_average.gte' => 7.0, 'vote_count.gte' => 5000],
        ],
    ];

    public static function get(?string $key): ?array
    {
        return $key && isset(self::THEMES[$key]) ? self::THEMES[$key] + ['key' => $key] : null;
    }

    public static function keys(): array
    {
        return array_keys(self::THEMES);
    }

    public static function all(): array
    {
        return collect(self::THEMES)->map(fn ($theme, $key) => $theme + ['key' => $key])->values()->all();
    }
}
