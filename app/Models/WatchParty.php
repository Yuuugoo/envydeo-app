<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A Watch Along: a shared movie clock + timestamped reactions for friends who press play
 * at the same moment on their own streaming services. Envydeo never streams the video.
 */
class WatchParty extends Model
{
    use Prunable;

    public const MAX_MEMBERS = 20;

    public const LIFETIME_HOURS = 24;

    public const COUNTDOWN_MS = 5000;   // "5, 4, 3, 2, 1, press play"

    public const RESUME_COUNTDOWN_MS = 3000;

    /** Parties end on their own this long after the credits. */
    public const OVERRUN_SECONDS = 20 * 60;

    protected $fillable = [
        'code', 'tmdb_id', 'media_type', 'season', 'episode', 'movie', 'runtime_seconds', 'status',
        'base_ms', 'resumed_at_ms', 'last_event', 'last_event_at_ms', 'ended_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'movie' => 'array',
            'base_ms' => 'integer',
            'resumed_at_ms' => 'integer',
            'last_event_at_ms' => 'integer',
            'runtime_seconds' => 'integer',
            'ended_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(WatchPartyMember::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(WatchPartyReaction::class);
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now())->where('status', '!=', 'ended');
    }

    public static function newCode(): string
    {
        do {
            $code = collect(range(1, 6))->map(fn () => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)])->implode('');
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    public static function sessionKey(int $partyId): string
    {
        return "watch_along.{$partyId}";
    }

    /** Position on the shared clock in ms. Negative while the countdown is still running. */
    public function positionMs(?int $nowMs = null): int
    {
        if ($this->status !== 'playing' || $this->resumed_at_ms === null) {
            return $this->base_ms;
        }

        return $this->base_ms + (($nowMs ?? self::nowMs()) - $this->resumed_at_ms);
    }

    /** Position in whole seconds, never below 0 (reactions during a countdown count as 0:00). */
    public function positionSeconds(?int $nowMs = null): int
    {
        return max(0, intdiv(max(0, $this->positionMs($nowMs)), 1000));
    }

    public function isOver(): bool
    {
        return $this->status === 'ended';
    }

    public function shouldAutoEnd(?int $nowMs = null): bool
    {
        return $this->status !== 'ended'
            && $this->positionSeconds($nowMs) > $this->runtime_seconds + self::OVERRUN_SECONDS;
    }

    public function title(): string
    {
        $title = $this->movie['title'] ?? 'Watch Along';

        return $this->media_type === 'tv' && $this->season && $this->episode
            ? "{$title} · S{$this->season} E{$this->episode}"
            : $title;
    }

    public function titleUrl(): string
    {
        return route($this->media_type === 'tv' ? 'show.show' : 'movie.show', $this->tmdb_id);
    }

    public function memberFor(?string $token, ?User $user = null): ?WatchPartyMember
    {
        if ($user && ($byAccount = $this->members()->where('user_id', $user->getKey())->first())) {
            return $byAccount;
        }
        if (!$token) {
            return null;
        }
        $byToken = $this->members()->where('token', hash('sha256', $token))->first();

        // A leftover session token never lets a signed-in person act as someone else's account.
        if ($byToken && $byToken->user_id && $byToken->user_id !== $user?->getKey()) {
            return null;
        }

        return $byToken;
    }

    /** @return array{0: WatchPartyMember, 1: string} the member and their secret token */
    public function addMember(string $name, bool $isHost = false, ?string $userId = null): array
    {
        $token = Str::random(40);
        $member = $this->members()->create([
            'name' => Str::limit(trim($name), 40, ''),
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'is_host' => $isHost,
            'last_seen_at' => now(),
        ]);

        return [$member, $token];
    }

    public static function formatTime(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return $seconds >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60)
            : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
