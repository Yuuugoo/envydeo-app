<?php

namespace App\Notifications;

class FollowAccepted extends SocialNotification
{
    protected function text(): string
    {
        return 'accepted your follow request';
    }

    protected function url(): string
    {
        return $this->actor->profileUrl() ?? route('feed');
    }
}
