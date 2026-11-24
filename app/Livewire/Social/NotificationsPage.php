<?php

namespace App\Livewire\Social;

use App\Models\Follow;
use App\Models\User;
use App\Services\SocialGraph;
use Livewire\Component;
use Livewire\WithPagination;

/** /notifications — all notifications, follow requests (accept/decline) and the push toggle. */
class NotificationsPage extends Component
{
    use WithPagination;

    public function mount(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function accept(string $userId, SocialGraph $graph): void
    {
        $graph->accept(auth()->user(), User::findOrFail($userId));
    }

    public function decline(string $userId, SocialGraph $graph): void
    {
        $graph->decline(auth()->user(), User::findOrFail($userId));
    }

    public function savePreferences(bool $friendCheckins): void
    {
        auth()->user()->update(['notify_friend_checkins' => $friendCheckins]);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.social.notifications-page', [
            'requests' => User::whereIn('id', Follow::where('followee_id', $user->id)->where('status', 'pending')->pluck('follower_id'))->get(),
            'notifications' => $user->notifications()->latest()->paginate(25),
            'vapidKey' => config('webpush.vapid.public_key'),
            'notifyCheckins' => $user->notify_friend_checkins,
        ]);
    }
}
