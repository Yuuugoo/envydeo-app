<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A logged watch: an optional Envydeo verdict (loved/good/meh/skip), "best with friends" tag and a short take. */
class DiaryEntry extends Model
{
    /** key => [emoji, label]. Order matters: best first. */
    public const VERDICTS = [
        'loved' => ['😍', 'Loved it'],
        'good' => ['👍', 'Good'],
        'meh' => ['😐', 'Meh'],
        'skip' => ['👎', 'Skip it'],
    ];

    /** Verdicts that count as "recommend" on title pages. */
    public const RECOMMEND = ['loved', 'good'];

    protected $fillable = ['user_id', 'tmdb_id', 'media_type', 'movie', 'verdict', 'best_with_friends', 'review', 'has_spoilers', 'watched_on'];

    protected function casts(): array
    {
        return ['movie' => 'array', 'has_spoilers' => 'boolean', 'best_with_friends' => 'boolean', 'watched_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (DiaryEntry $entry) => Activity::where('subject_type', $entry->getMorphClass())
            ->where('subject_id', $entry->getKey())->delete());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ReviewLike::class);
    }

    public function verdictEmoji(): ?string
    {
        return self::VERDICTS[$this->verdict][0] ?? null;
    }

    public function verdictLabel(): ?string
    {
        return self::VERDICTS[$this->verdict][1] ?? null;
    }

    /** "tonight", "last night", "on Mon", "Nov 3": a watch moment, not a diary date. */
    public function watchedWhen(): string
    {
        $d = $this->watched_on;

        return match (true) {
            $d->isToday() => 'tonight',
            $d->isYesterday() => 'last night',
            $d->gt(now()->subWeek()) => 'on ' . $d->format('l'),
            $d->year === now()->year => 'on ' . $d->format('M j'),
            default => 'on ' . $d->format('M j, Y'),
        };
    }

    public function titleUrl(): string
    {
        return route($this->media_type === 'tv' ? 'show.show' : 'movie.show', $this->tmdb_id);
    }
}
