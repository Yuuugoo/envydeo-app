<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Watchlist;
use App\Support\TitleSnapshot;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Toggle a movie or TV show on the signed-in user's watchlist. */
class AddWatchlist extends Component
{
    #[Locked]
    public $tmdbId;

    #[Locked]
    public string $type = 'movie';

    public $isInWatchlist = false;

    public function mount($tmdbId, string $type = 'movie')
    {
        $this->tmdbId = (int) $tmdbId;
        $this->type = $type === 'tv' ? 'tv' : 'movie';
        $this->checkWatchlistStatus();
    }

    private function query()
    {
        return Watchlist::where('user_id', Auth::id())->where('tmdb_id', $this->tmdbId)->where('media_type', $this->type);
    }

    public function checkWatchlistStatus()
    {
        if (Auth::check()) {
            $this->isInWatchlist = $this->query()->exists();
        }
    }

    public function toggleWatchlist()
    {
        if (!Auth::check()) {
            session()->flash('error', 'Please login to add items to your watchlist.');

            return redirect()->route('login');
        }

        if ($existing = $this->query()->first()) {
            $existing->delete();
            $this->isInWatchlist = false;

            return null;
        }

        Watchlist::create(['user_id' => Auth::id(), 'tmdb_id' => $this->tmdbId, 'media_type' => $this->type]);
        $this->isInWatchlist = true;

        // Shows up in followers' feeds (only for people who've set up a social profile).
        $user = Auth::user();
        if ($user->username && ($snapshot = TitleSnapshot::fetch($this->tmdbId, $this->type))) {
            Activity::record($user, 'watchlisted', null, ['movie' => $snapshot]);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.add-watchlist');
    }
}
