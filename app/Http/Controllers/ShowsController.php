<?php

namespace App\Http\Controllers;

use App\Services\MovieNightDealer;
use App\Services\Tmdb;
use App\Services\ViewerRegion;

class ShowsController extends Controller
{
    public function __construct(private Tmdb $tmdb) {}

    public function list()
    {
        $page = max(1, min(500, (int) request()->get('page', 1)));

        $popularTV = $this->tmdb->get('/trending/tv/day', ['page' => $page], Tmdb::HOUR);

        return view('shows.index', [
            'popularTV' => ['results' => $popularTV['results'] ?? []] + $popularTV,
            'genres' => $this->tmdb->tvGenres(),
            'totalPages' => min($popularTV['total_pages'] ?? 1, 500),
            'currentPage' => $popularTV['page'] ?? $page,
        ]);
    }

    public function show($id, MovieNightDealer $dealer, ViewerRegion $regions)
    {
        $selectedTV = $this->tmdb->get("/tv/{$id}", ['append_to_response' => 'credits,videos,images,external_ids'], Tmdb::HOURS_6);

        abort_if(empty($selectedTV['id']), 404);

        // Every season + recommendations + similar, all requested in parallel.
        $requests = [
            'recommendations' => ["/tv/{$id}/recommendations"],
            'similar' => ["/tv/{$id}/similar"],
        ];

        foreach ($selectedTV['seasons'] ?? [] as $season) {
            if ($season['season_number'] === 0) {
                continue; // skip "Specials"
            }
            $requests['season-'.$season['season_number']] = ["/tv/{$id}/season/{$season['season_number']}"];
        }

        $responses = $this->tmdb->many($requests, Tmdb::HOURS_6);

        $seasonsWithEpisodes = [];
        foreach ($responses as $key => $data) {
            if (str_starts_with($key, 'season-')) {
                // Only the fields the page uses; full episodes carry huge guest_stars/crew arrays.
                $seasonsWithEpisodes[(int) substr($key, 7)] = collect($data['episodes'] ?? [])
                    ->map(fn ($e) => collect($e)->only(['id', 'name', 'episode_number', 'still_path', 'runtime', 'overview', 'vote_average', 'air_date']))
                    ->values()
                    ->all();
            }
        }

        return view('shows.details', [
            'tvShow' => $selectedTV,
            'selectedTVDetails' => [
                'media_type' => 'TV',
                'genres' => collect($selectedTV['genres'] ?? [])->pluck('name')->toArray(),
            ],
            'genres' => $this->tmdb->tvGenres(),
            'similarTV' => $responses['similar']['results'] ?? [],
            'recommendedTV' => $responses['recommendations']['results'] ?? [],
            'episodes' => $seasonsWithEpisodes,
            'whereToWatch' => $dealer->whereToWatch((int) $id, $region = $regions->resolve(request()), 'tv'),
            'region' => $region,
            'regions' => $regions->available(),
        ]);
    }
}
