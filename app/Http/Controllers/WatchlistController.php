<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class WatchlistController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to view your watchlist.');
        }

        $watchlistItems = \App\Models\Watchlist::where('user_id', $user->id)->get();

        $movies = [];

        foreach ($watchlistItems as $item) {
            $response = Http::withToken(config('services.tmdb.token'))
                ->get("https://api.themoviedb.org/3/movie/{$item->tmdb_id}");

            if ($response->successful()) {
                $movies[] = $response->json();
            }
        }

        return view('watchlist', compact('movies'));
    }
}
