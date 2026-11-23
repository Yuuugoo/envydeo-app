<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Services\Tmdb;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function index(Tmdb $tmdb)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Please login to view your watchlist.');
        }

        $tmdbIds = Watchlist::where('user_id', $user->id)->latest()->pluck('tmdb_id');

        // All watchlist movies fetched in parallel (and cached for a day).
        $details = $tmdb->many(
            $tmdbIds->mapWithKeys(fn ($id) => [$id => ["/movie/{$id}"]])->all(),
            Tmdb::DAY
        );

        $movies = collect($details)->filter(fn ($movie) => ! empty($movie['id']))->values()->all();

        return view('watchlist', compact('movies'));
    }
}
