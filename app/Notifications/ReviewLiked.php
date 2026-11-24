<?php

namespace App\Notifications;

use App\Models\DiaryEntry;
use App\Models\User;

class ReviewLiked extends SocialNotification
{
    public function __construct(User $actor, public DiaryEntry $entry)
    {
        parent::__construct($actor);
    }

    protected function text(): string
    {
        return 'liked your review of ' . ($this->entry->movie['title'] ?? 'a movie');
    }

    protected function url(): string
    {
        return $this->entry->titleUrl() . '#reviews';
    }
}
