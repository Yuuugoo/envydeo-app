<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WatchProgress;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{

    public function index()
    {
        $genres = Cache::remember('genres', 60 * 24 * 7, function () {
            $genreLists = Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/genre/movie/list')
                ->json()['genres'];

            return collect($genreLists)->mapWithKeys(function ($genre){
                return [$genre['id'] => $genre['name']];
            });
        });

        $moviesWithDetails = Cache::remember('visual_movies', 60 * 24, function () {
            return $this->getVisuallyImpressiveMovies();
        });

        $continueWatchingMovies = Auth::check() ? $this->getContinueWatchingMovies() : collect();

        return view('index', [
            'genres' => $genres,
            'moviesWithDetails' => $moviesWithDetails,
            'continueWatchingMovies' => $continueWatchingMovies,
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
            ->get('https://api.themoviedb.org/3/movie/'. $id.'?append_to_response=credits,videos')
            ->json();
            
        $genres = collect($selectedMovie['genres'])->pluck('name')->toArray();
        $selectedMovieDetails = ([
            'media_type' => 'Movie',
            'genres' => $genres,
        ]);

        return view('movies.details', [
            'movie' => $selectedMovie,
            'selectedMovieDetails' => $selectedMovieDetails,
        ]);
    }

    public function getSimilarMovies($id)
    {
        $similarMovies = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/'. $id.'/similar')
            ->json()['results'];

        $genreLists = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/genre/movie/list')
            ->json()['genres'];
       
        $genres = collect($genreLists)->mapWithKeys(function ($genre){
            return [$genre['id'] => $genre['name']];
        });

        return response()->json([
            'similarMovie' => $similarMovies,
            'genres' => $genres
        ]);
    }

    public function getRecommendedMovies($id)
    {
        $recommendedMovies = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/'. $id.'/recommendations')
            ->json()['results'];

        $genreLists = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/genre/movie/list')
            ->json()['genres'];
       
        $genres = collect($genreLists)->mapWithKeys(function ($genre){
            return [$genre['id'] => $genre['name']];
        });

        return response()->json([
            'recommendedMovie' => $recommendedMovies,
            'genres' => $genres
        ]);
    }


    public function getMovieImages($id)
    {
        $images = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/'. $id.'/images')
            ->json();

        return response()->json($images);
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
            if (count($mediaWithDetails) >= 15) break;
        }

        return $mediaWithDetails;
    }


    private function getContinueWatchingMovies()
    {
        $userId = Auth::id();

        $watchProgressItems = WatchProgress::where('user_id', $userId)
            ->where('media_type', 'movie')
            ->where('watched', '>', 0)
            ->whereRaw('(watched / duration) < 0.95')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();
        
        $movieIds = $watchProgressItems->pluck('tmdb_id')->toArray();
        
        if (empty($movieIds)) {
            return collect();
        }

        $movies = collect();
        foreach ($movieIds as $movieId) {
            try {
                $response = Http::withToken(config('services.tmdb.token'))
                    ->get("https://api.themoviedb.org/3/movie/{$movieId}", [
                        'language' => 'en-US',
                    ]);
                
                if ($response->successful()) {
                    $movieData = $response->json();
                    
                    $watchProgress = $watchProgressItems->firstWhere('tmdb_id', $movieId);
                    $movieData['watch_progress'] = [
                        'watched' => $watchProgress->watched,
                        'duration' => $watchProgress->duration,
                        'percentage' => $watchProgress->watched_percentage,
                        'updated_at' => $watchProgress->updated_at,
                    ];
                    
                    $movies->push($movieData);
                    Log::info('Added movie: ' . $movieData['title']);
                } else {
                    Log::error("TMDB API failed for movie ID {$movieId}: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch movie data for ID {$movieId}: " . $e->getMessage());
            }
        }
        
        Log::info('Final movies collection count: ' . $movies->count());
        return $movies;
    }


    public function loadTopRatedMovies()
    {
        $topRatedMovies = Cache::remember('top_movies', 60 * 24, function () {
            return Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/movie/top_rated')
                ->json()['results'];
        });

        return response()->json([
            'movies' => $topRatedMovies,
            'success' => true
        ]);

    }

    public function loadPopularMovies()
    {
        $popularMovies = Cache::remember('popular_movies', 60 * 24, function () {
            return Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/movie/popular')
                ->json()['results'];
        });

        return response()->json([
            'movies' => $popularMovies,
            'success' => true
        ]);
    }

    public function loadPopularTV()
    {
        $popularTV = Cache::remember('popular_tv', 60 * 24, function () {
            return Http::withToken(config('services.tmdb.token'))
                ->get('https://api.themoviedb.org/3/trending/tv/day?')
                ->json()['results'];
        });

        return response()->json([
            'shows' => $popularTV,
            'success' => true
        ]);
    }

    public function loadMoviesByGenre($id)
    {
        $cacheKey = 'genre_movies_' . $id;

        $genreMovies = Cache::remember($cacheKey, 60 * 24, function () use ($id) {
            $allMovies = [];

            for ($page = 1; $page <= 3; $page++) {
                $response = Http::withToken(config('services.tmdb.token'))
                    ->get('https://api.themoviedb.org/3/discover/movie', [
                        'with_genres' => $id,
                        'sort_by' => 'popularity.desc',
                        'language' => 'en-US',
                        'with_original_language' => 'en',
                        'vote_count.gte' => 50,
                        'page' => $page,
                        'include_adult' => false,
                    ]);

                if ($response->successful()) {
                    $results = $response->json()['results'] ?? [];
                    $allMovies = array_merge($allMovies, $results);
                } else {
                    break;
                }

                usleep(200000);
            }

            return $allMovies;
        });

        return response()->json([
            'movies' => $genreMovies,
            'success' => true,
        ]);
    }

    public function collage()
    {
        $response = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/popular', [
                'language' => 'en-US',
                'page' => 1,
            ]);

        if ($response->successful()) {
            $movies = $response->json()['results'];
            return response()->json([
                'success' => true,
                'movies' => $movies,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch data from TMDB',
        ], 500);
    }


}
