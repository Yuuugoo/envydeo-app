<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\MovieNightController;
use App\Http\Controllers\MovieNightStatsController;
use App\Http\Controllers\ShowsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\OAuthProviderController;
use App\Http\Controllers\WatchProgressController;

Route::get('/', [MovieController::class, 'index'])->name('movie.index');
Route::get('/movies', [MovieController::class, 'list'])->name('movie.list');
Route::get('/movies/{id}', [MovieController::class, 'show'])->whereNumber('id')->name('movie.show');
Route::get('/movies/{id}/similar', [MovieController::class, 'getSimilarMovies'])->whereNumber('id');
Route::get('/movies/{id}/recommended', [MovieController::class, 'getRecommendedMovies'])->whereNumber('id');
Route::get('/movies/{id}/images', [MovieController::class, 'getMovieImages'])->whereNumber('id');

Route::get('/api/movies/popular', [MovieController::class, 'loadPopularMovies']);
Route::get('/api/movies/top-rated', [MovieController::class, 'loadTopRatedMovies']);
Route::get('/api/movies/genre/{id}', [MovieController::class, 'loadMoviesByGenre'])->whereNumber('id');

Route::get('/api/movies/collage', [MovieController::class, 'collage']);

Route::get('/api/tv/popular', [MovieController::class, 'loadPopularTV']);


Route::get('/shows', [ShowsController::class, 'list'])->name('show.list');
Route::get('/shows/{id}', [ShowsController::class, 'show'])->whereNumber('id')->name('show.show');

Route::get('/search', [SearchController::class, 'results'])->name('search.results');

// Movie Night Matcher
Route::prefix('movie-night')->name('movie-night.')->group(function () {
    Route::get('/', [MovieNightController::class, 'index'])->name('index');
    Route::get('/join', [MovieNightController::class, 'join'])->name('join');
    Route::post('/quick', [MovieNightController::class, 'quick'])->middleware('throttle:20,1')->name('quick');

    Route::middleware('auth')->group(function () {
        Route::post('/watchlist', [MovieNightController::class, 'watchlist'])->middleware('throttle:20,1')->name('watchlist');
        Route::get('/us', [MovieNightController::class, 'us'])->name('us');
        Route::post('/us', [MovieNightController::class, 'createUs'])->middleware('throttle:10,1')->name('us.create');
        Route::get('/stats', MovieNightStatsController::class)->middleware('can:view-movie-night-stats')->name('stats');
    });

    Route::get('/{code}', [MovieNightController::class, 'show'])->where('code', '[A-Za-z0-9]{6}')->name('show');
    Route::get('/{code}/preview.png', [MovieNightController::class, 'preview'])->where('code', '[A-Za-z0-9]{6}')->middleware('throttle:60,1')->name('preview');
    Route::get('/{code}/card.png', [MovieNightController::class, 'card'])->where('code', '[A-Za-z0-9]{6}')->middleware('throttle:60,1')->name('card');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::post('/watch-progress', [WatchProgressController::class, 'store']);
    Route::get('/watch-progress', [WatchProgressController::class, 'index']);
    Route::delete('/watch-progress/{tmdbId}', [WatchProgressController::class, 'destroy']);
});


Route::middleware(['guest'])
    ->prefix('login')
    ->name('login.')
    ->group(function () {
        Route::get('{provider}', [OAuthProviderController::class, 'index'])->name('provider');
        Route::get('{provider}/envydeo/callback', [OAuthProviderController::class, 'store'])->name('provider.callback');
    });

    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');

require __DIR__.'/auth.php';
