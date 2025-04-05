<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MovieController extends Controller
{
    public function index()
    {
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

        return view('index', compact('popularMovies', 'genres', 'topRatedMovies'));
    }

    public function show($id)
    {
        $selectedMovie = Http::withToken(config('services.tmdb.token'))
            ->get('https://api.themoviedb.org/3/movie/'. $id.'?append_to_response=credits,videos,images')
            ->json(); 

        return view('details', [
            'movie' => $selectedMovie,
        ]);
    }
}
