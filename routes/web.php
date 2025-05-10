<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ShowsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OAuthProviderController;

Route::get('/', [MovieController::class, 'index'])->name('movie.index');
Route::get('/movies', [MovieController::class, 'list'])->name('movie.list');
Route::get('/movies/{id}', [MovieController::class, 'show'])->name('movie.show');
Route::get('/shows', [ShowsController::class, 'list'])->name('show.list');
Route::get('/shows/{id}', [ShowsController::class, 'show'])->name('show.show');

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

require __DIR__.'/auth.php';
