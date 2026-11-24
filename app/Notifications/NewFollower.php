<?php

namespace App\Notifications;

class NewFollower extends SocialNotification
{
    protected function text(): string
    {
        return 'started following you';
    }

    protected function url(): string
    {
        return $this->actor->profileUrl() ?? route('feed');
    }
}
