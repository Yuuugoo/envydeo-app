<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Feed item. visibility "public" = whoever can see the actor's profile;
 * "friends" = mutual follows only (check-ins).
 */
class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'type', 'subject_type', 'subject_id', 'data', 'visibility'];

    protected function casts(): array
    {
        return ['data' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(User $user, string $type, ?Model $subject = null, array $data = [], string $visibility = 'public'): self
    {
        return static::create([
            'user_id' => $user->getKey(),
            'type' => $type,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'data' => $data ?: null,
            'visibility' => $visibility,
        ]);
    }
}
