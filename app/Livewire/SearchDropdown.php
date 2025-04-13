<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;


class SearchDropdown extends Component
{
    public $search = '';

    public function render()
    {
        $searchResults = [];

        if (strlen($this->search) >= 2) {
            $token = config('services.tmdb.token');
            
            // Fetch TV shows and movies
            $tvResults = Http::withToken($token)
                ->get('https://api.themoviedb.org/3/search/tv', [
                    'query' => $this->search,
                    'include_adult' => false, // Exclude adult content
                ])
                ->json()['results'];

            $movieResults = Http::withToken($token)
                ->get('https://api.themoviedb.org/3/search/movie', [
                    'query' => $this->search,
                    'include_adult' => false,
                ])
                ->json()['results'];

            $tvResults = array_map(function ($item) {
                return array_merge($item, ['media_type' => 'tv']);
            }, $tvResults);

            $movieResults = array_map(function ($item) {
                return array_merge($item, ['media_type' => 'movie']);
            }, $movieResults);

            $searchResults = collect(array_merge($tvResults, $movieResults))
                ->sortByDesc('popularity')
                ->take(12);
        }

        return view('livewire.search-dropdown', [
            'searchResults' => $searchResults
        ]);
        
    }
}
