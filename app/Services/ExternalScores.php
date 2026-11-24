<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IMDb / Rotten Tomatoes / Metacritic scores via the OMDb API (free key: https://www.omdbapi.com/apikey.aspx).
 * Set OMDB_API_KEY in .env. Without a key this quietly returns nothing and the UI hides the scores.
 * Results are cached for a day to stay within OMDb's daily limit.
 */
class ExternalScores
{
    public function enabled(): bool
    {
        return filled(config('services.omdb.key'));
    }

    /** @return array{imdb: ?string, imdb_votes: ?string, rotten_tomatoes: ?string, metacritic: ?string}|null */
    public function forImdbId(?string $imdbId): ?array
    {
        if (!$this->enabled() || !$imdbId || !preg_match('/^tt\d+$/', $imdbId)) {
            return null;
        }

        return Cache::remember("omdb:{$imdbId}", Tmdb::DAY, function () use ($imdbId) {
            try {
                $data = Http::timeout(5)->get('https://www.omdbapi.com/', [
                    'i' => $imdbId,
                    'apikey' => config('services.omdb.key'),
                ])->json();
            } catch (\Throwable $e) {
                Log::warning('OMDb request failed', ['imdb' => $imdbId, 'error' => $e->getMessage()]);

                return null;
            }

            if (($data['Response'] ?? 'False') !== 'True') {
                return null;
            }

            $ratings = collect($data['Ratings'] ?? [])->pluck('Value', 'Source');
            $clean = fn ($v) => $v && $v !== 'N/A' ? $v : null;

            return [
                'imdb' => $clean($data['imdbRating'] ?? null),
                'imdb_votes' => $clean($data['imdbVotes'] ?? null),
                'rotten_tomatoes' => $clean($ratings['Rotten Tomatoes'] ?? null),
                'metacritic' => $clean($ratings['Metacritic'] ?? null),
            ];
        });
    }
}
