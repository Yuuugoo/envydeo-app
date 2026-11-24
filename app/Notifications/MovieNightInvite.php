<?php

namespace App\Notifications;

use App\Models\MatchRoom;
use App\Models\User;

class MovieNightInvite extends SocialNotification
{
    public function __construct(User $actor, public MatchRoom $room)
    {
        parent::__construct($actor);
    }

    protected function text(): string
    {
        return 'invited you to movie night 🍿 Tap to join';
    }

    protected function url(): string
    {
        return route('movie-night.show', $this->room->code);
    }

    protected function tag(): ?string
    {
        return 'movienight:' . $this->room->code;
    }
}
