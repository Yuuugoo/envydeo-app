<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    public function index()
    {

        // These are my API's for my popular and top rated carousel
        $popularMovies = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/popular')
            ->json()['results'];


        $topRatedMovies = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/top_rated')
            ->json()['results'];
        
        $genreLists = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/genre/movie/list')
            ->json()['genres'];
        
        $genres = collect($genreLists)->mapWithKeys(function ($genre){
            return [$genre['id'] => $genre['name']];
        });    
        
        $popularTV = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/trending/tv/day?')
            ->json()['results'];
        
        // // This is the api that I used for my backdrop carousel
        // $moviesWithDetails = [];
        // foreach ($topRatedMovies as $movie) {
        //     $details = Http::withToken(config('services.tmdb.token'))
        //         ->get("https://api.themoviedb.org/3/movie/{$movie['id']}", [
        //             'append_to_response' => 'videos,external_ids',
        //         ])
        //         ->json();

        //     $movieGenres = collect($details['genres'] ?? [])
        //         ->pluck('name')
        //         ->toArray();

        //     $trailer = collect($details['videos']['results'] ?? [])
        //         ->firstWhere('type', 'Trailer');

        //     $moviesWithDetails[] = array_merge($movie, [
        //         'runtime' => $details['runtime'] ?? null,
        //         'media_type' => 'Movie',
        //         'genres' => $movieGenres,
        //         'imdb_id' => $details['external_ids']['imdb_id'] ?? null,
        //         'trailer_key' => $trailer['key'] ?? null,
        //     ]);
        // }
        

        // This means once the data is cached, it will serve the same data for 24 hours without calling the API again.
        $moviesWithDetails = Cache::remember('visual_movies', 60 * 24, function () {
            return $this->getVisuallyImpressiveMovies();
        });
        
        // dump($moviesWithDetails);
        return view('index', [
            'popularMovies' => $popularMovies,
            'genres' => $genres,
            'topRatedMovies' => $topRatedMovies,
            'moviesWithDetails' => $moviesWithDetails,
            'popularTV' => $popularTV,
        ]);
    }

    public function list()
    {
        $page = request()->get('page', 1);
        
        $response = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/now_playing', [
                'page' => $page
            ])
            ->json();
        
        $nowPlayingMovies = $response['results'];
        $totalPages = $response['total_pages'];
        $currentPage = $response['page'];
        
        $genreLists = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/genre/movie/list')
            ->json()['genres'];

        $genres = collect($genreLists)->mapWithKeys(function ($genre){
                return [$genre['id'] => $genre['name']];
            });
            
        return view('movies.index', [
            'nowPlayingMovies' => $nowPlayingMovies,
            'genres' => $genres,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    public function show($id)
    {
        $selectedMovie = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/'. $id.'?append_to_response=credits,videos,images')
            ->json();

        $genres = collect($selectedMovie['genres'])->pluck('name')->toArray();

        $selectedMovieDetails = ([
            'media_type' => 'Movie',
            'genres' => $genres,
        ]);

        $recommendedMovie = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/'. $id.'/recommendations')
            ->json()['results'];

        $similarMovie = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/'. $id.'/similar')
            ->json()['results'];

        // dump($recommendedMovie);

        $genreLists = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/genre/movie/list')
            ->json()['genres'];
        
        $genres = collect($genreLists)->mapWithKeys(function ($genre){
            return [$genre['id'] => $genre['name']];
        });    

        return view('movies.details', [
            'movie' => $selectedMovie,
            'selectedMovieDetails' => $selectedMovieDetails,
            'genres' => $genres,
            'similarMovie' => $similarMovie,
            'recommendedMovie' => $recommendedMovie,
        ]);
    }

    // This is a sample fucntion provided by claude to make show visually appealing backdrop on my index page carousel
    private function getVisuallyImpressiveMovies()
    {
        // Visual-heavy genres: Sci-fi (878), Adventure (12), Fantasy (14), Action (28)
        $visualGenres = '878,12,14,10749';
        
        // Fetch standard visual movies (original query)
        $visualMoviesResponse = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/discover/movie', [
                'sort_by' => 'popularity.desc',
                'with_genres' => $visualGenres,
                'page' => 1,
                'include_adult' => false
            ]);
        
        // Get popular TV shows, sort by newest first
        $popularTVResponse = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/discover/tv', [
                'sort_by' => 'first_air_date.desc', // Sort by newest first
                'page' => 1,
                'include_adult' => false,
                'first_air_date.gte' => date('Y-m-d', strtotime('-1 year')), // Shows from the last year
            ]);
        
        // Get trending TV shows for this week
        $trendingTVResponse = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/trending/tv/week', [
                'page' => 1,
            ]);
        
        // Combine all results from the queries
        $potentialMovies = $visualMoviesResponse->json()['results'];
        $potentialTVShows = array_merge(
            $popularTVResponse->json()['results'],
            $trendingTVResponse->json()['results']
        );
        
        // Mark media types BEFORE merging
        foreach ($potentialMovies as &$movie) {
            $movie['media_type'] = 'movie';
        }
        
        foreach ($potentialTVShows as &$tvShow) {
            $tvShow['media_type'] = 'tv';
        }
        
        // Remove references to prevent unexpected behavior
        unset($movie);
        unset($tvShow);
        
        // Combine all results together
        $allPotentialMedia = array_merge($potentialMovies, $potentialTVShows);
        
        // Shuffle the combined results
        shuffle($allPotentialMedia);
        
        $mediaWithDetails = [];
        foreach ($allPotentialMedia as $media) {
            // Ensure the item has a backdrop image for visual appeal
            if (empty($media['backdrop_path'])) continue;
            
            if ($media['media_type'] === 'tv') {
                // Fetch detailed TV show info
                $details = Http::withToken(config('services.tmdb.token'))
                    ->get("https://api.themoviedb.org/3/tv/{$media['id']}", [
                        'append_to_response' => 'videos,external_ids,credits',
                    ])
                    ->json();
                
                if (empty($details['id'])) continue;
                
                // Extract genre names for the TV show
                $genres = collect($details['genres'] ?? [])
                    ->pluck('name')
                    ->toArray();
                
                // Get the first available trailer if present
                $trailer = collect($details['videos']['results'] ?? [])
                    ->firstWhere('type', 'Trailer');
                
                // Merge the details into the TV show array
                $mediaWithDetails[] = array_merge($media, [
                    'number_of_seasons' => $details['number_of_seasons'] ?? null,
                    'media_type' => 'TV',
                    'genres' => $genres,
                    'imdb_id' => $details['external_ids']['imdb_id'] ?? null,
                    'trailer_key' => $trailer['key'] ?? null,
                    'status' => $details['status'] ?? null,
                    'first_air_date' => $details['first_air_date'] ?? null
                ]);
            } else {
                // Fetch detailed movie info, including videos and external IDs
                $details = Http::withToken(config('services.tmdb.token'))
                    ->get("https://api.themoviedb.org/3/movie/{$media['id']}", [
                        'append_to_response' => 'videos,external_ids',
                    ])
                    ->json();
                
                if (empty($details['id'])) continue;
                
                // Extract genre names for the movie
                $genres = collect($details['genres'] ?? [])
                    ->pluck('name')
                    ->toArray();
                
                // Get the first available trailer if present
                $trailer = collect($details['videos']['results'] ?? [])
                    ->firstWhere('type', 'Trailer');
                
                // Merge the details into the movie array
                $mediaWithDetails[] = array_merge($media, [
                    'runtime' => $details['runtime'] ?? null,
                    'media_type' => 'Movie',
                    'genres' => $genres,
                    'imdb_id' => $details['external_ids']['imdb_id'] ?? null,
                    'trailer_key' => $trailer['key'] ?? null,
                    'language' => $details['original_language'] ?? 'en',
                ]);
            }
            
            // Stop once we have enough detailed items
            if (count($mediaWithDetails) >= 15) break;
        }

        return $mediaWithDetails;
    }




}
