<?php

namespace App\Http\Controllers;

use App\Models\WatchProgress;
use App\Services\Tmdb;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    public function __construct(private Tmdb $tmdb) {}

    public function index()
    {
        $moviesWithDetails = Cache::remember('home:carousel', Tmdb::HOURS_6, fn () => $this->getVisuallyImpressiveMovies());

        return view('index', [
            'genres' => $this->tmdb->movieGenres(),
            'moviesWithDetails' => $moviesWithDetails,
            'continueWatchingMovies' => Auth::check() ? $this->getContinueWatchingMovies() : collect(),
        ]);
    }

    public function list()
    {
        $page = max(1, min(500, (int) request()->get('page', 1)));

        $response = $this->tmdb->get('/movie/now_playing', ['page' => $page], Tmdb::HOUR);

        return view('movies.index', [
            'nowPlayingMovies' => $response['results'] ?? [],
            'genres' => $this->tmdb->movieGenres(),
            'currentPage' => $response['page'] ?? $page,
            'totalPages' => min($response['total_pages'] ?? 1, 500), // TMDB caps paging at 500
        ]);
    }

    public function show($id)
    {
        $selectedMovie = $this->tmdb->get("/movie/{$id}", ['append_to_response' => 'credits,videos'], Tmdb::HOURS_6);

        abort_if(empty($selectedMovie['id']), 404);

        return view('movies.details', [
            'movie' => $selectedMovie,
            'selectedMovieDetails' => [
                'media_type' => 'Movie',
                'genres' => collect($selectedMovie['genres'] ?? [])->pluck('name')->toArray(),
            ],
        ]);
    }

    public function getSimilarMovies($id)
    {
        return response()->json([
            'similarMovie' => $this->tmdb->get("/movie/{$id}/similar", [], Tmdb::DAY)['results'] ?? [],
            'genres' => $this->tmdb->movieGenres(),
        ]);
    }

    public function getRecommendedMovies($id)
    {
        return response()->json([
            'recommendedMovie' => $this->tmdb->get("/movie/{$id}/recommendations", [], Tmdb::DAY)['results'] ?? [],
            'genres' => $this->tmdb->movieGenres(),
        ]);
    }

    public function getMovieImages($id)
    {
        return response()->json($this->tmdb->get("/movie/{$id}/images", [], Tmdb::DAY));
    }

    /**
     * Trending movies + TV with backdrops for the home page hero carousel.
     * 2 parallel trending calls, then up to 15 parallel detail calls.
     */
    private function getVisuallyImpressiveMovies(): Collection
    {
        $trending = $this->tmdb->many([
            'movie' => ['/trending/movie/week'],
            'tv' => ['/trending/tv/week'],
        ], Tmdb::HOURS_6);

        $allMedia = collect($trending['movie']['results'] ?? [])
            ->map(fn ($m) => array_merge($m, ['media_type' => 'Movie']))
            ->merge(collect($trending['tv']['results'] ?? [])->map(fn ($tv) => array_merge($tv, ['media_type' => 'TV'])))
            ->filter(fn ($m) => ! empty($m['backdrop_path']))
            ->shuffle()
            ->take(15)
            ->values();

        $details = $this->tmdb->many(
            $allMedia->mapWithKeys(fn ($m) => [
                $m['media_type'].'-'.$m['id'] => [
                    ($m['media_type'] === 'TV' ? '/tv/' : '/movie/').$m['id'],
                    ['append_to_response' => 'videos,external_ids'],
                ],
            ])->all(),
            Tmdb::DAY
        );

        return $allMedia->map(function ($media) use ($details) {
            $detail = $details[$media['media_type'].'-'.$media['id']] ?? [];

            if (empty($detail['id'])) {
                return null;
            }

            $common = [
                'id' => $media['id'],
                'overview' => $media['overview'] ?? '',
                'backdrop_path' => $media['backdrop_path'],
                'poster_path' => $media['poster_path'] ?? null,
                'vote_average' => $media['vote_average'] ?? 0,
                'genres' => collect($detail['genres'] ?? [])->pluck('name')->toArray(),
                'trailer_key' => collect($detail['videos']['results'] ?? [])->firstWhere('type', 'Trailer')['key'] ?? null,
                'imdb_id' => $detail['external_ids']['imdb_id'] ?? null,
            ];

            if ($media['media_type'] === 'TV') {
                return $common + [
                    'title' => $media['name'] ?? $detail['name'] ?? '',
                    'media_type' => 'TV',
                    'release_date' => $media['first_air_date'] ?? $detail['first_air_date'] ?? null,
                    'number_of_seasons' => $detail['number_of_seasons'] ?? null,
                    'status' => $detail['status'] ?? null,
                    'first_air_date' => $detail['first_air_date'] ?? null,
                ];
            }

            return $common + [
                'title' => $media['title'] ?? $detail['title'] ?? '',
                'media_type' => 'Movie',
                'release_date' => $media['release_date'] ?? $detail['release_date'] ?? null,
                'runtime' => $detail['runtime'] ?? null,
                'language' => $detail['original_language'] ?? 'en',
            ];
        })->filter()->values();
    }

    private function getContinueWatchingMovies(): Collection
    {
        $watchProgressItems = WatchProgress::where('user_id', Auth::id())
            ->where('media_type', 'movie')
            ->where('watched', '>', 0)
            ->whereRaw('(watched / duration) < 0.95')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        if ($watchProgressItems->isEmpty()) {
            return collect();
        }

        $details = $this->tmdb->many(
            $watchProgressItems->mapWithKeys(fn ($p) => [$p->tmdb_id => ["/movie/{$p->tmdb_id}", ['language' => 'en-US']]])->all(),
            Tmdb::DAY
        );

        return $watchProgressItems->map(function ($progress) use ($details) {
            $movie = $details[$progress->tmdb_id] ?? [];

            if (empty($movie['id'])) {
                return null;
            }

            $movie['watch_progress'] = [
                'watched' => $progress->watched,
                'duration' => $progress->duration,
                'percentage' => $progress->watched_percentage,
                'updated_at' => $progress->updated_at,
            ];

            return $movie;
        })->filter()->values();
    }

    public function loadTopRatedMovies()
    {
        return response()->json([
            'movies' => $this->tmdb->get('/movie/top_rated', [], Tmdb::DAY)['results'] ?? [],
            'success' => true,
        ]);
    }

    public function loadPopularMovies()
    {
        return response()->json([
            'movies' => $this->popularMovies(),
            'success' => true,
        ]);
    }

    public function loadPopularTV()
    {
        return response()->json([
            'shows' => $this->tmdb->get('/trending/tv/day', [], Tmdb::HOURS_6)['results'] ?? [],
            'success' => true,
        ]);
    }

    /**
     * 3 pages of popular English movies in a genre, fetched in parallel.
     */
    public function loadMoviesByGenre($id)
    {
        $pages = $this->tmdb->many(
            collect([1, 2, 3])->mapWithKeys(fn ($page) => [$page => ['/discover/movie', [
                'with_genres' => $id,
                'sort_by' => 'popularity.desc',
                'language' => 'en-US',
                'with_original_language' => 'en',
                'vote_count.gte' => 50,
                'page' => $page,
                'include_adult' => 'false',
            ]]])->all(),
            Tmdb::DAY
        );

        return response()->json([
            'movies' => collect($pages)->flatMap(fn ($p) => $p['results'] ?? [])->values(),
            'success' => true,
        ]);
    }

    public function collage()
    {
        $movies = $this->popularMovies();

        return response()->json(
            $movies ? ['success' => true, 'movies' => $movies] : ['success' => false, 'error' => 'Failed to fetch data from TMDB'],
            $movies ? 200 : 502
        );
    }

    private function popularMovies(): array
    {
        return $this->tmdb->get('/movie/popular', ['language' => 'en-US', 'page' => 1], Tmdb::HOURS_6)['results'] ?? [];
    }
}
