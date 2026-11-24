<?php

namespace App\Livewire\Social;

use App\Services\Feed as FeedService;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/** /feed — "Tonight" strip (friends' live check-ins) + activity from people you follow. */
class Feed extends Component
{
    /** Opens the Tonight viewer at this check-in (used by notification links). */
    #[Url]
    public ?int $tonight = null;

    public int $limit = 15;

    public function loadMore(): void
    {
        $this->limit = min($this->limit + 15, 150);
    }

    #[On('social-changed')]
    public function refreshFeed(): void
    {
        // re-render after follows/unfollows from suggestions
    }

    public function render(FeedService $feed)
    {
        $viewer = auth()->user();
        $groups = $feed->tonight($viewer);
        $activities = $feed->activities($viewer, $this->limit);

        $openGroup = null;
        $openIndex = 0;
        if ($this->tonight) {
            foreach ($groups as $g => $group) {
                $i = $group['checkIns']->search(fn ($c) => $c->id === $this->tonight);
                if ($i !== false) {
                    [$openGroup, $openIndex] = [$g, $i];
                    break;
                }
            }
        }

        return view('livewire.social.feed', [
            'viewer' => $viewer,
            'groups' => $groups,
            'activities' => $activities,
            'hasMore' => $activities->count() >= $this->limit && $this->limit < 150,
            'suggestions' => $feed->suggestions($viewer),
            'openGroup' => $openGroup,
            'openIndex' => $openIndex,
        ]);
    }
}
