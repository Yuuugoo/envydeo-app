<?php

namespace App\Livewire\Social;

use App\Models\DiaryEntry;
use App\Models\ReviewLike;
use App\Notifications\ReviewLiked;
use App\Services\SocialGraph;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** A rating/review with likes. Visible to whoever can see the author's profile. */
class ReviewCard extends Component
{
    #[Locked]
    public int $entryId;

    public bool $showTitle = true;

    public function mount(int $entryId, bool $showTitle = true): void
    {
        $this->entryId = $entryId;
        $this->showTitle = $showTitle;
    }

    public function toggleLike(SocialGraph $graph)
    {
        if (!auth()->check()) {
            return $this->redirectRoute('login');
        }

        $entry = $this->entry($graph);
        $viewer = auth()->user();
        abort_if($entry->user_id === $viewer->id, 403);

        $like = ReviewLike::where('diary_entry_id', $entry->id)->where('user_id', $viewer->id)->first();
        if ($like) {
            $like->delete();
        } else {
            ReviewLike::create(['diary_entry_id' => $entry->id, 'user_id' => $viewer->id]);
            $entry->user->notify(new ReviewLiked($viewer, $entry));
        }

        return null;
    }

    private function entry(SocialGraph $graph): DiaryEntry
    {
        $entry = DiaryEntry::with('user')->findOrFail($this->entryId);
        abort_unless($graph->canViewProfile(auth()->user(), $entry->user), 403);

        return $entry;
    }

    public function render(SocialGraph $graph)
    {
        $entry = $this->entry($graph)->loadCount('likes');

        return view('livewire.social.review-card', [
            'entry' => $entry,
            'liked' => auth()->check() && ReviewLike::where('diary_entry_id', $entry->id)->where('user_id', auth()->id())->exists(),
            'isOwner' => $entry->user_id === auth()->id(),
        ]);
    }
}
