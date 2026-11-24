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

        $items = Watchlist::where('user_id', $user->id)->latest()->get(['tmdb_id', 'media_type']);

        // Movies and shows fetched in parallel (and cached for a day), newest first.
        $details = $tmdb->many(
            $items->mapWithKeys(fn ($w) => [$w->media_type . '-' . $w->tmdb_id => [$w->tmdbPath()]])->all(),
            Tmdb::DAY
        );

        $movies = $items
            ->map(fn ($w) => ($d = $details[$w->media_type . '-' . $w->tmdb_id] ?? []) && !empty($d['id']) ? $d + ['media_type' => $w->media_type] : null)
            ->filter()->values()->all();

        return view('watchlist', compact('movies'));
    }
}
