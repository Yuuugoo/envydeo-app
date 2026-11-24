<?php

namespace App\Notifications;

use App\Models\CheckIn;
use App\Models\CheckInReaction;
use App\Models\User;

class CheckInReacted extends SocialNotification
{
    public function __construct(User $actor, public CheckIn $checkIn, public string $reaction)
    {
        parent::__construct($actor);
    }

    protected function text(): string
    {
        $title = $this->checkIn->movie['title'] ?? 'your check-in';

        return $this->reaction === 'in'
            ? "is in for {$title} 🙋"
            : 'reacted ' . (CheckInReaction::REACTIONS[$this->reaction] ?? '👍') . " to your {$title} check-in";
    }

    protected function url(): string
    {
        return route('feed', ['tonight' => $this->checkIn->id]);
    }

    protected function tag(): ?string
    {
        return 'reaction:' . $this->checkIn->id;
    }
}
