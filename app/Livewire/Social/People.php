<?php

namespace App\Livewire\Social;

use App\Models\User;
use App\Services\Feed;
use App\Services\SocialGraph;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /people — find people by name or @username; suggestions when the search is empty. */
class People extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public function render(SocialGraph $graph, Feed $feed)
    {
        $viewer = auth()->user();
        $term = ltrim(trim($this->query), '@');

        $results = mb_strlen($term) >= 2
            ? User::whereNotNull('username')
                ->whereNotIn('id', [...$graph->hiddenIds($viewer), $viewer->id])
                ->where(function ($q) use ($term) {
                    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
                    $q->where('username', 'like', strtolower($like))->orWhere('name', 'like', $like);
                })
                ->orderByRaw('username = ? desc', [strtolower($term)])
                ->limit(30)->get()
            : collect();

        return view('livewire.social.people', [
            'results' => $results,
            'suggestions' => $results->isEmpty() ? $feed->suggestions($viewer, 12) : collect(),
            'searching' => mb_strlen($term) >= 2,
        ]);
    }
}
