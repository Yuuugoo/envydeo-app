<?php

namespace App\Http\Controllers;

use App\Models\MovieNightEvent;
use App\Services\MovieNightDealer;
use App\Services\Tmdb;
use App\Services\ViewerRegion;
use App\Support\MovieNightThemes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    public function __construct(private Tmdb $tmdb) {}

    /**
     * Home. Signed-in users with a profile get "Tonight" (friends, overlaps, rooms).
     * Everyone else gets the landing page: a playable Movie Night demo deck, how it works,
     * mood presets and what people matched on this week.
     */
    public function index()
    {
        if (auth()->user()?->username) {
            return view('home.tonight');
        }

        return view('index', [
            'genres' => $this->tmdb->movieGenres(),
            'deck' => Cache::remember('home:demo-deck', Tmdb::HOURS_6, fn () => $this->demoDeck()),
            'mostMatched' => Cache::remember('home:most-matched', Tmdb::HOUR, fn () => $this->mostMatched()),
            'themes' => MovieNightThemes::THEMES,
        ]);
    }

    /** 12 well-known trending movies with posters for the landing-page swipe demo. */
    private function demoDeck(): array
    {
        return collect($this->tmdb->get('/trending/movie/week', [], Tmdb::HOURS_6)['results'] ?? [])
            ->filter(fn ($m) => !empty($m['poster_path']) && ($m['vote_count'] ?? 0) >= 100)
            ->take(12)
            ->map(fn ($m) => [
                'id' => $m['id'],
                'title' => $m['title'] ?? '',
                'poster_path' => $m['poster_path'],
                'year' => substr($m['release_date'] ?? '', 0, 4),
                'rating' => round($m['vote_average'] ?? 0, 1),
                'genre_ids' => $m['genre_ids'] ?? [],
            ])
            ->values()->all();
    }

    /**
     * Movies most often matched in Movie Night rooms over the last 7 days, from the
     * anonymous `matched` events (only the TMDB id is used). Empty until there is data.
     */
    private function mostMatched(): array
    {
        $counts = MovieNightEvent::where('event', 'matched')->where('created_at', '>=', now()->subWeek())
            ->pluck('meta')
            ->map(fn ($meta) => (int) ($meta['tmdb_id'] ?? 0))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(12);

        if ($counts->isEmpty()) {
            return [];
        }

        $details = $this->tmdb->many($counts->keys()->mapWithKeys(fn ($id) => [$id => ["/movie/{$id}"]])->all(), Tmdb::DAY);

        return $counts->map(fn ($n, $id) => empty($details[$id]['id']) ? null : [
            'id' => $id,
            'title' => $details[$id]['title'] ?? '',
            'poster_path' => $details[$id]['poster_path'] ?? null,
            'matches' => $n,
        ])->filter()->values()->all();
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

    public function show($id, MovieNightDealer $dealer, ViewerRegion $regions)
    {
        $selectedMovie = $this->tmdb->get("/movie/{$id}", ['append_to_response' => 'credits,videos'], Tmdb::HOURS_6);

        abort_if(empty($selectedMovie['id']), 404);

        $region = $regions->resolve(request());

        return view('movies.details', [
            'movie' => $selectedMovie,
            'selectedMovieDetails' => [
                'media_type' => 'Movie',
                'genres' => collect($selectedMovie['genres'] ?? [])->pluck('name')->toArray(),
            ],
            'whereToWatch' => $dealer->whereToWatch((int) $id, $region),
            'region' => $region,
            'regions' => $regions->available(),
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
