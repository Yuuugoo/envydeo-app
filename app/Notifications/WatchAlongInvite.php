<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WatchParty;

class WatchAlongInvite extends SocialNotification
{
    public function __construct(User $actor, public WatchParty $party)
    {
        parent::__construct($actor);
    }

    protected function text(): string
    {
        return 'wants you to watch ' . $this->party->title() . ' together 🎬 Tap to join';
    }

    protected function url(): string
    {
        return route('watch-along.show', $this->party->code);
    }

    protected function tag(): ?string
    {
        return 'watchalong:' . $this->party->code;
    }
}
