<?php

namespace App\Notifications;

use App\Models\CheckIn;
use App\Models\User;

/** The Locket-style moment: "Ana is watching Dune 🍿" lands on friends' phones. */
class FriendCheckedIn extends SocialNotification
{
    public function __construct(User $actor, public CheckIn $checkIn)
    {
        parent::__construct($actor);
    }

    protected function text(): string
    {
        $title = $this->checkIn->movie['title'] ?? 'something';

        return "is watching {$title} 🍿" . ($this->checkIn->photo_path ? ' 📷' : '');
    }

    protected function url(): string
    {
        return route('feed', ['tonight' => $this->checkIn->id]);
    }

    protected function tag(): ?string
    {
        return 'checkin:' . $this->actor->id;
    }
}
