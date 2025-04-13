<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShowsController extends Controller
{
    
    public function list()
    {
        $page = request()->get('page', 1);

        $popularTV = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/trending/tv/day?', [
                'page' => $page,
            ])
            ->json();

        $genreLists = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/genre/movie/list')
            ->json()['genres'];
        
        $genres = collect($genreLists)->mapWithKeys(function ($genre){
            return [$genre['id'] => $genre['name']];
        });

        $totalPages = $popularTV['total_pages'];
        $currentPage = $popularTV['page'];

        return view('shows.index',[
            'popularTV' => $popularTV,
            'genres' => $genres,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,

        ]);
    }

    public function show($id)
    {
        $selectedTV = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/tv/'. $id.'?append_to_response=credits,videos,images')
            ->json();

        $genres = collect($selectedTV['genres'])->pluck('name')->toArray();

        $selectedTVDetails = [
            'media_type' => 'TV',
            'genres' => $genres,
        ];

        $seasonsWithEpisodes = [];
        

        foreach ($selectedTV['seasons'] as $season) {
            // Skip special seasons (season 0)
            if ($season['season_number'] === 0) continue;
            
            $seasonData = Http::withToken(config('services.tmdb.token'))
                ->get("https://api.themoviedb.org/3/tv/{$id}/season/{$season['season_number']}")
                ->json();
            
            $seasonsWithEpisodes[$season['season_number']] = $seasonData['episodes'];
        }

        $recommendedTV = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/tv/'. $id.'/recommendations')
            ->json()['results'];

        $similarTV = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/tv/'. $id.'/similar')
            ->json()['results'];

        $genreLists = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/genre/tv/list')
            ->json()['genres'];
        
        $genres = collect($genreLists)->mapWithKeys(function ($genre) {
            return [$genre['id'] => $genre['name']];
        });    

        // dump($selectedTV);
        // dump($recommendedTV);
        return view('shows.details', [
            'tvShow' => $selectedTV,
            'selectedTVDetails' => $selectedTVDetails,
            'genres' => $genres,
            'similarTV' => $similarTV,
            'recommendedTV' => $recommendedTV,
            'episodes' => $seasonsWithEpisodes,
        ]);
    }
}
