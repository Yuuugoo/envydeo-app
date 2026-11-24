<?php

namespace App\Livewire\Social;

use App\Models\CheckIn;
use App\Models\CheckInReaction;
use App\Models\Watchlist;
use App\Notifications\CheckInReacted;
use App\Services\SocialGraph;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** One "Tonight" check-in with one-tap reactions (no comments, Locket-style). Friends-only. */
class CheckInCard extends Component
{
    #[Locked]
    public int $checkInId;

    public string $variant = 'feed'; // feed | story

    public function mount(int $checkInId, string $variant = 'feed'): void
    {
        $this->checkInId = $checkInId;
        $this->variant = $variant;
    }

    public function react(string $reaction, SocialGraph $graph): void
    {
        abort_unless(array_key_exists($reaction, CheckInReaction::REACTIONS), 422);
        $checkIn = $this->checkIn($graph);
        $viewer = auth()->user();
        abort_if($checkIn->user_id === $viewer->id, 403);

        $existing = CheckInReaction::where('check_in_id', $checkIn->id)->where('user_id', $viewer->id)->first();

        if ($existing?->reaction === $reaction) {
            $existing->delete(); // tap again to undo

            return;
        }

        CheckInReaction::updateOrCreate(['check_in_id' => $checkIn->id, 'user_id' => $viewer->id], ['reaction' => $reaction]);

        if ($reaction === 'in' && $checkIn->media_type === 'movie') {
            Watchlist::firstOrCreate(['user_id' => $viewer->id, 'tmdb_id' => $checkIn->tmdb_id]);
        }

        $checkIn->user->notify(new CheckInReacted($viewer, $checkIn, $reaction));
    }

    public function delete(SocialGraph $graph)
    {
        $checkIn = $this->checkIn($graph);
        abort_unless($checkIn->user_id === auth()->id(), 403);
        $checkIn->delete();

        return $this->redirectRoute('feed');
    }

    private function checkIn(SocialGraph $graph): CheckIn
    {
        $checkIn = CheckIn::with('user')->findOrFail($this->checkInId);
        abort_unless($graph->canViewFriendsContent(auth()->user(), $checkIn->user), 403);

        return $checkIn;
    }

    public function render(SocialGraph $graph)
    {
        $checkIn = $this->checkIn($graph)->load('reactions.user');
        $mine = $checkIn->reactions->firstWhere('user_id', auth()->id());

        return view('livewire.social.check-in-card', [
            'checkIn' => $checkIn,
            'isOwner' => $checkIn->user_id === auth()->id(),
            'mine' => $mine?->reaction,
            'counts' => $checkIn->reactions->countBy('reaction'),
        ]);
    }
}
