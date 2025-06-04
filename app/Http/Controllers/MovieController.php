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

    // This is a sample fucntion provided by claude to show visually appealing backdrop on my index page carousel
    private function getVisuallyImpressiveMovies()
    {
        $token = config('services.tmdb.token');
        
        // Fetch trending content
        $trendingMovies = Http::withToken($token)
            ->get('https://api.themoviedb.org/3/trending/movie/week')
            ->json()['results'] ?? [];
        
        $trendingTV = Http::withToken($token)
            ->get('https://api.themoviedb.org/3/trending/tv/week')
            ->json()['results'] ?? [];
        
        // Combine and shuffle all content
        $allMedia = collect($trendingMovies)
            ->map(fn($m) => array_merge($m, ['media_type' => 'Movie']))
            ->merge(
                collect($trendingTV)->map(fn($tv) => array_merge($tv, ['media_type' => 'TV']))
            )
            ->filter(fn($m) => !empty($m['backdrop_path']))
            ->shuffle()
            ->take(15);
        
        $mediaWithDetails = [];
        
        foreach ($allMedia as $media) {
            if ($media['media_type'] === 'TV') {
                // Fetch detailed TV show info
                $details = Http::withToken($token)
                    ->get("https://api.themoviedb.org/3/tv/{$media['id']}", [
                        'append_to_response' => 'videos,external_ids',
                    ])
                    ->json();
                
                if (empty($details['id'])) continue;
                
                $genres = collect($details['genres'] ?? [])->pluck('name')->toArray();
                $trailer = collect($details['videos']['results'] ?? [])
                    ->firstWhere('type', 'Trailer');
                
                $mediaWithDetails[] = [
                    'id' => $media['id'],
                    'title' => $media['name'] ?? $details['name'],
                    'overview' => $media['overview'],
                    'backdrop_path' => $media['backdrop_path'],
                    'poster_path' => $media['poster_path'],
                    'media_type' => 'TV',
                    'vote_average' => $media['vote_average'],
                    'release_date' => $media['first_air_date'] ?? $details['first_air_date'],
                    'genres' => $genres,
                    'number_of_seasons' => $details['number_of_seasons'] ?? null,
                    'trailer_key' => $trailer['key'] ?? null,
                    'imdb_id' => $details['external_ids']['imdb_id'] ?? null,
                    'status' => $details['status'] ?? null,
                    'first_air_date' => $details['first_air_date'] ?? null,
                ];
            } else {
                // Fetch detailed movie info
                $details = Http::withToken($token)
                    ->get("https://api.themoviedb.org/3/movie/{$media['id']}", [
                        'append_to_response' => 'videos,external_ids',
                    ])
                    ->json();
                
                if (empty($details['id'])) continue;
                
                $genres = collect($details['genres'] ?? [])->pluck('name')->toArray();
                $trailer = collect($details['videos']['results'] ?? [])
                    ->firstWhere('type', 'Trailer');
                
                $mediaWithDetails[] = [
                    'id' => $media['id'],
                    'title' => $media['title'] ?? $details['title'],
                    'overview' => $media['overview'],
                    'backdrop_path' => $media['backdrop_path'],
                    'poster_path' => $media['poster_path'],
                    'media_type' => 'Movie',
                    'vote_average' => $media['vote_average'],
                    'release_date' => $media['release_date'] ?? $details['release_date'],
                    'genres' => $genres,
                    'runtime' => $details['runtime'] ?? null,
                    'trailer_key' => $trailer['key'] ?? null,
                    'imdb_id' => $details['external_ids']['imdb_id'] ?? null,
                    'language' => $details['original_language'] ?? 'en',
                ];
            }
        }
        
        return collect($mediaWithDetails);
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
        $topRatedMovies = Cache::remember('top_movies', 60 * 24 * 7, function () {
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
