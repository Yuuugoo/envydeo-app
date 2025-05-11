<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class SearchDropdown extends Component
{
    public $search = '';
    public $isSearching = false;

    public function updatedSearch()
    {
        $this->isSearching = true;
    }

    public function render()
    {
        $searchResults = [];

        if (strlen($this->search) >= 2) {
            $token = config('services.tmdb.token');

            $results = Http::withToken($token)
                ->get('https://api.themoviedb.org/3/search/multi', [
                    'query' => $this->search,
                    'include_adult' => false,
                ])
                ->json();

            if (isset($results['results'])) {
                $searchResults = collect($results['results'])
                    ->filter(function ($item) {
                        return in_array($item['media_type'], ['tv', 'movie']);
                    })
                    ->sortByDesc('popularity')
                    ->take(4)
                    ->values();
            }
        }

        $this->isSearching = false;

        return view('livewire.search-dropdown', [
            'searchResults' => $searchResults
        ]);
    }

    public function searchAll()
    {
        if (strlen($this->search) >= 2) {
            return redirect()->route('search.results', ['query' => $this->search]);
        }
    }
}
