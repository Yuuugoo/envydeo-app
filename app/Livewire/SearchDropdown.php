<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\Tmdb;

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
        $searchResults = collect();

        if (strlen($this->search) >= 2) {
            $results = app(Tmdb::class)->get('/search/multi', [
                'query' => trim($this->search),
                'include_adult' => 'false',
            ], Tmdb::MINUTES_10);

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
