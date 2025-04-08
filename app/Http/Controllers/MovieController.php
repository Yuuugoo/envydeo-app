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
            'moviesWithDetails' => $moviesWithDetails
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

        return view('details', [
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
        
        // First, fetch Korean movies with high production value
        // $koreanMoviesResponse = Http::withToken(config('services.tmdb.token'))
        //     ->get('https://api.themoviedb.org/3/discover/movie', [
        //         'sort_by' => 'popular.desc',
        //         'with_original_language' => 'ko', // Korean language filter
        //         'page' => 1,
        //         'include_adult' => false
        //     ]);
        
        // // Fetch standard visual movies (original query)
        $visualMoviesResponse = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/discover/movie', [
                'sort_by' => 'popularity.desc',
                'with_genres' => $visualGenres,
                'page' => 1,
                'include_adult' => false
            ]);
        
        // Fetch zombie movies using a keyword filter (update the keyword id as needed)
        $zombieMoviesResponse = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/discover/movie', [
                'sort_by' => 'popular.desc',
                'page' => 1,
                'include_adult' => false
            ]);
        
        // Combine all movie results from the three queries
        $potentialMovies = array_merge(
            // $koreanMoviesResponse->json()['results'],
            $visualMoviesResponse->json()['results'],
            $zombieMoviesResponse->json()['results']
        );

        // Combine all results together
        $allPotentialMovies = array_merge(
            $potentialMovies, 
        );
        
        // Shuffle the combined results
        shuffle($allPotentialMovies);
        
        $moviesWithDetails = [];
        foreach ($allPotentialMovies as $movie) {
            // Ensure the movie has a backdrop image for visual appeal
            if (empty($movie['backdrop_path'])) continue;
            
            // Fetch detailed info, including videos and external IDs
            $details = Http::withToken(config('services.tmdb.token'))
                ->get("https://api.themoviedb.org/3/movie/{$movie['id']}", [
                    'append_to_response' => 'videos,external_ids',
                ])
                ->json();
            
            if (empty($details['id'])) continue;
            
            // Extract genre names for the movie
            $movieGenres = collect($details['genres'] ?? [])
                ->pluck('name')
                ->toArray();
            
            // Get the first available trailer if present
            $trailer = collect($details['videos']['results'] ?? [])
                ->firstWhere('type', 'Trailer');
            
            // Merge the details into the movie array, including a flag for Korean movies
            $moviesWithDetails[] = array_merge($movie, [
                'runtime' => $details['runtime'] ?? null,
                'media_type' => 'Movie',
                'genres' => $movieGenres,
                'imdb_id' => $details['external_ids']['imdb_id'] ?? null,
                'trailer_key' => $trailer['key'] ?? null,
                'language' => $details['original_language'] ?? 'en',
                'is_korean' => ($details['original_language'] ?? '') === 'ko'
            ]);
            
            // Stop once we have 20 detailed movies
            if (count($moviesWithDetails) >= 15) break;
        }
        
        return $moviesWithDetails;
    }




}
