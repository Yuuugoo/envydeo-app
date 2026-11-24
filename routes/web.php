<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\MovieNightController;
use App\Http\Controllers\MovieNightStatsController;
use App\Http\Controllers\CheckInPhotoController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ShowsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\OAuthProviderController;

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

// "Where to watch" country picker: remembered for a year; "auto" goes back to IP detection.
Route::post('/region', function (\Illuminate\Http\Request $request, \App\Services\ViewerRegion $regions) {
    $code = strtoupper((string) $request->input('region'));
    $cookie = isset($regions->available()[$code])
        ? cookie()->forever(\App\Services\ViewerRegion::COOKIE, $code)
        : cookie()->forget(\App\Services\ViewerRegion::COOKIE);

    return redirect()->to(url()->previous() . '#where-to-watch')->withCookie($cookie);
})->middleware('throttle:30,1')->name('region.update');

// Social
Route::middleware('auth')->group(function () {
    Route::view('/welcome', 'social.welcome')->name('welcome');
    Route::get('/check-ins/{checkIn}/photo', CheckInPhotoController::class)->name('check-ins.photo');

    Route::middleware('username')->group(function () {
        Route::view('/feed', 'social.feed')->name('feed');
        Route::view('/people', 'social.people')->name('people');
        Route::view('/notifications', 'social.notifications')->name('notifications.index');
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:20,1')->name('push.store');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');
        Route::view('/admin/reports', 'social.admin-reports')->middleware('can:moderate')->name('admin.reports');
    });
});
Route::get('/@{username}', [UserProfileController::class, 'show'])->where('username', '[a-z0-9_.]+')->name('profile.show');
Route::get('/@{username}/{list}', [UserProfileController::class, 'connections'])
    ->where(['username' => '[a-z0-9_.]+', 'list' => 'followers|following'])->name('profile.connections');

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



Route::middleware(['guest'])
    ->prefix('login')
    ->name('login.')
    ->group(function () {
        Route::get('{provider}', [OAuthProviderController::class, 'index'])->name('provider');
        Route::get('{provider}/envydeo/callback', [OAuthProviderController::class, 'store'])->name('provider.callback');
    });

    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');

require __DIR__.'/auth.php';
