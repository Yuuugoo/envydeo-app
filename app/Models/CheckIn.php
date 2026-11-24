<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** "Tonight" check-in: what someone is watching right now. Friends-only; in the Tonight strip for 24h. */
class CheckIn extends Model
{
    public const PHOTO_DISK = 'local'; // private (storage/app/private) — served only through CheckInPhotoController

    protected $fillable = ['user_id', 'tmdb_id', 'media_type', 'movie', 'caption', 'photo_path', 'expires_at'];

    protected function casts(): array
    {
        return ['movie' => 'array', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Photos are personal data: remove the file with the check-in (incl. account deletion cascades handled in User).
        static::deleting(function (CheckIn $checkIn) {
            $checkIn->deletePhoto();
            Activity::where('subject_type', $checkIn->getMorphClass())->where('subject_id', $checkIn->getKey())->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CheckInReaction::class);
    }

    public function isLive(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? route('check-ins.photo', $this) . '?v=' . $this->updated_at?->timestamp : null;
    }

    public function titleUrl(): string
    {
        return route($this->media_type === 'tv' ? 'show.show' : 'movie.show', $this->tmdb_id);
    }

    public function deletePhoto(): void
    {
        if ($this->photo_path) {
            Storage::disk(self::PHOTO_DISK)->delete($this->photo_path);
        }
    }
}
