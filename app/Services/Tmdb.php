<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for every TMDB API call.
 *
 * - Every successful response is cached (TTL in seconds).
 * - Uncached requests passed to many() are sent concurrently via Http::pool,
 *   so a page that needs 15 TMDB calls waits for ~1 round trip instead of 15.
 * - Failed requests return [] and are never cached.
 */
class Tmdb
{
    public const BASE_URL = 'https://api.themoviedb.org/3';

    public const IMAGE_URL = 'https://image.tmdb.org/t/p/';

    public const MINUTES_10 = 600;

    public const HOUR = 3600;

    public const HOURS_6 = 21600;

    public const DAY = 86400;

    public const WEEK = 604800;

    /** Max concurrent requests per batch (TMDB allows ~50 req/s). */
    private const POOL_SIZE = 20;

    /**
     * Fetch one endpoint (cached).
     */
    public function get(string $path, array $query = [], int $ttl = self::HOUR): array
    {
        return $this->many(['result' => [$path, $query]], $ttl)['result'];
    }

    /**
     * Fetch several endpoints at once. Cached ones are read from cache,
     * the rest are requested in parallel.
     *
     * @param  array<string|int, array{0: string, 1?: array}>  $requests  key => [path, query]
     * @return array<string|int, array> key => decoded JSON ([] on failure)
     */
    public function many(array $requests, int $ttl = self::HOUR): array
    {
        $results = [];
        $missing = [];

        foreach ($requests as $key => $request) {
            $path = $request[0];
            $query = $request[1] ?? [];
            $cacheKey = $this->cacheKey($path, $query);

            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $results[$key] = $cached;
            } else {
                $missing[$key] = [$path, $query, $cacheKey];
            }
        }

        foreach (array_chunk($missing, self::POOL_SIZE, true) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                foreach ($chunk as $key => [$path, $query]) {
                    $pool->as((string) $key)
                        ->withToken(config('services.tmdb.token'))
                        ->acceptJson()
                        ->connectTimeout(5)
                        ->timeout(10)
                        ->retry(2, 200, throw: false)
                        ->get(self::BASE_URL.$path, $query);
                }
            });

            foreach ($chunk as $key => [$path, , $cacheKey]) {
                $response = $responses[(string) $key] ?? null;
                $data = $response instanceof Response && $response->successful() ? $response->json() : null;

                if (is_array($data)) {
                    Cache::put($cacheKey, $data, $ttl);
                    $results[$key] = $data;

                    continue;
                }

                Log::warning('TMDB request failed', [
                    'path' => $path,
                    'status' => $response instanceof Response ? $response->status() : null,
                    'error' => $response instanceof \Throwable ? $response->getMessage() : null,
                ]);
                $results[$key] = [];
            }
        }

        // Keep the caller's key order.
        return array_replace(array_fill_keys(array_keys($requests), []), $results);
    }

    /** @return Collection<int, string> genre id => name */
    public function movieGenres(): Collection
    {
        return $this->genres('movie');
    }

    /** @return Collection<int, string> genre id => name */
    public function tvGenres(): Collection
    {
        return $this->genres('tv');
    }

    private function genres(string $type): Collection
    {
        return collect($this->get("/genre/{$type}/list", [], self::WEEK)['genres'] ?? [])
            ->pluck('name', 'id');
    }

    private function cacheKey(string $path, array $query): string
    {
        ksort($query);

        return 'tmdb:'.md5($path.'?'.http_build_query($query));
    }
}
