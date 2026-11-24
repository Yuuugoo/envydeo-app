<?php

namespace App\Livewire\Social;

use Livewire\Component;

/** Nav bell: unread count (polled) and the latest notifications. */
class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.social.notification-bell', [
            'unread' => $user->unreadNotifications()->count(),
            'latest' => $user->notifications()->latest()->limit(8)->get(),
        ]);
    }
}
