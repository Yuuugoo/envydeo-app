<?php

namespace App\Notifications;

class FollowRequested extends SocialNotification
{
    protected function text(): string
    {
        return 'wants to follow you';
    }

    protected function url(): string
    {
        return route('notifications.index');
    }

    public function toArray(object $notifiable): array
    {
        return parent::toArray($notifiable) + ['kind' => 'follow_request'];
    }
}
