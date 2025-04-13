<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\ShowsController;
use Illuminate\Support\Facades\Route;


Route::get('/', [MovieController::class, 'index'])->name('movie.index');
Route::get('/movies', [MovieController::class, 'list'])->name('movie.list');
Route::get('/movies/{id}', [MovieController::class, 'show'])->name('movie.show');



Route::get('/shows', [ShowsController::class, 'list'])->name('show.list');
Route::get('/shows/{id}', [ShowsController::class, 'show'])->name('show.show');
