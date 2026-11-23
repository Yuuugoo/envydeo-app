<?php

namespace App\Models;

use App\Support\MovieNightThemes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MatchRoom extends Model
{
    use Prunable;

    public const MAX_PARTICIPANTS = 12;

    public const MAX_US_PARTICIPANTS = 2;

    public const LIFETIME_HOURS = 24;

    protected $fillable = [
        'code', 'status', 'source', 'theme', 'is_persistent', 'filters', 'candidates',
        'next_page', 'matched_tmdb_id', 'started_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'candidates' => 'array',
            'is_persistent' => 'boolean',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MatchVote::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(MatchResult::class);
    }

    /** Expired regular rooms are deleted by `php artisan model:prune` (scheduled daily). "Us" rooms never expire. */
    public function prunable(): Builder
    {
        return static::whereNotNull('expires_at')->where('expires_at', '<', now());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Short, unambiguous, unique join code (no 0/O/1/I). */
    public static function newCode(): string
    {
        do {
            $code = collect(range(1, 6))
                ->map(fn () => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)])
                ->implode('');
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function maxParticipants(): int
    {
        return $this->is_persistent ? self::MAX_US_PARTICIPANTS : self::MAX_PARTICIPANTS;
    }

    public function theme(): ?array
    {
        return MovieNightThemes::get($this->theme);
    }

    /** @return list<int> */
    public function candidateIds(): array
    {
        return array_map(fn ($c) => (int) $c['id'], $this->candidates ?? []);
    }

    public function candidate(?int $tmdbId): ?array
    {
        return collect($this->candidates)->firstWhere('id', $tmdbId);
    }

    /**
     * The participant for this visitor: logged-in users are recognised by account
     * (so it works across devices), guests by the secret token in their session.
     */
    public function participantFor(?string $token, ?User $user = null): ?MatchParticipant
    {
        if ($user) {
            $byAccount = $this->participants()->where('user_id', $user->getKey())->first();
            if ($byAccount) {
                return $byAccount;
            }
        }

        if (!$token) {
            return null;
        }

        $byToken = $this->participants()->where('token', hash('sha256', $token))->first();

        // A leftover session token must never make a signed-in person act as someone else's account
        // (e.g. a shared computer where one person logged out and another logged in).
        if ($byToken && $byToken->user_id && $byToken->user_id !== $user?->getKey()) {
            return null;
        }

        return $byToken;
    }

    /** Adds a participant and returns [participant, plain secret token for the session]. */
    public function addParticipant(string $name, bool $isHost = false, ?string $userId = null): array
    {
        $token = Str::random(40);

        $participant = $this->participants()->create([
            'name' => trim($name),
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'is_host' => $isHost,
        ]);

        return [$participant, $token];
    }

    public static function sessionKey(int $roomId): string
    {
        return "movie_night.{$roomId}";
    }
}
