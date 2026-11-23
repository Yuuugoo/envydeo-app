<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Anonymous Movie Night analytics. Stores only the event name, the room and
 * non-personal details (e.g. theme, share channel) — never IPs, names or user ids.
 */
class MovieNightEvent extends Model
{
    public const UPDATED_AT = null;

    public const EVENTS = [
        'room_created', 'invite_opened', 'joined', 'started', 'matched',
        'invite_shared', 'card_shared',
    ];

    protected $fillable = ['event', 'match_room_id', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public static function record(string $event, ?MatchRoom $room = null, array $meta = []): void
    {
        if (!in_array($event, self::EVENTS, true)) {
            return;
        }

        static::create([
            'event' => $event,
            'match_room_id' => $room?->id,
            'meta' => $meta ?: null,
        ]);
    }
}
