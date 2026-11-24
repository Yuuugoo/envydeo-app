<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A logged watch: optional ½-star rating (stored 1–10) and optional review. */
class DiaryEntry extends Model
{
    protected $fillable = ['user_id', 'tmdb_id', 'media_type', 'movie', 'rating', 'review', 'has_spoilers', 'watched_on'];

    protected function casts(): array
    {
        return ['movie' => 'array', 'has_spoilers' => 'boolean', 'watched_on' => 'date', 'rating' => 'integer'];
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

    /** 1–10 → 0.5–5.0 stars */
    public function stars(): ?float
    {
        return $this->rating ? $this->rating / 2 : null;
    }

    public function titleUrl(): string
    {
        return route($this->media_type === 'tv' ? 'show.show' : 'movie.show', $this->tmdb_id);
    }
}
