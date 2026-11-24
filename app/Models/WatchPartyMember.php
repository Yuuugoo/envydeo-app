<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Someone in a Watch Along. Guests are allowed: identity is a secret token kept in their session. */
class WatchPartyMember extends Model
{
    protected $fillable = ['watch_party_id', 'user_id', 'name', 'token', 'is_host', 'last_seen_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['is_host' => 'boolean', 'last_seen_at' => 'datetime'];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(WatchParty::class, 'watch_party_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initials(): string
    {
        return Str::upper(collect(preg_split('/\s+/', trim($this->name)))->filter()->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->implode('')) ?: '?';
    }

    /** Seen in the last 20 seconds (the page polls every ~1.5s while open). */
    public function isHere(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subSeconds(20));
    }
}
