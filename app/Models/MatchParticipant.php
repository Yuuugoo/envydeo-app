<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchParticipant extends Model
{
    protected $fillable = ['name', 'user_id', 'token', 'is_host'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['is_host' => 'boolean'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(MatchRoom::class, 'match_room_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MatchVote::class);
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }
}
