<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckInReaction extends Model
{
    /** key => emoji. "in" = "I'm in" (also adds the title to the reactor's watchlist). */
    public const REACTIONS = ['popcorn' => '🍿', 'laugh' => '😂', 'cry' => '😭', 'scream' => '😱', 'love' => '❤️', 'in' => '🙋'];

    protected $fillable = ['check_in_id', 'user_id', 'reaction'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function emoji(): string
    {
        return self::REACTIONS[$this->reaction] ?? '👍';
    }
}
