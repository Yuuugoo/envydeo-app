<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Auth;

class AddWatchlist extends Component
{
    public $tmdbId;
    public $isInWatchlist = false;

    public function mount($tmdbId)
    {
        $this->tmdbId = $tmdbId;
        $this->checkWatchlistStatus();
    }

    public function checkWatchlistStatus()
    {
        if (Auth::check()) {
            $this->isInWatchlist = Watchlist::where('user_id', Auth::id())
                ->where('tmdb_id', $this->tmdbId)
                ->exists();
        }
    }

    public function toggleWatchlist()
    {
        if (!Auth::check()) {
            session()->flash('error', 'Please login to add items to your watchlist.');
            return redirect()->route('login');
        }

        $existingItem = Watchlist::where('user_id', Auth::id())
            ->where('tmdb_id', $this->tmdbId)
            ->first();

        if ($existingItem) {
            $existingItem->delete();
            $this->isInWatchlist = false;
            session()->flash('success', 'Removed from watchlist!');
        } else {
            Watchlist::create([
                'user_id' => Auth::id(),
                'tmdb_id' => $this->tmdbId,
            ]);
            $this->isInWatchlist = true;
            session()->flash('success', 'Added to watchlist!');

            // Shows up in followers' feeds (only for people who've set up a social profile).
            $user = Auth::user();
            if ($user->username && ($movie = \App\Support\TitleSnapshot::fetch((int) $this->tmdbId))) {
                \App\Models\Activity::record($user, 'watchlisted', null, ['movie' => $movie]);
            }
        }
    }

    public function render()
    {
        return view('livewire.add-watchlist');
    }
}