<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, InteractsWithMedia, HasPushSubscriptions;

    public const USERNAME_RULES = ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_.]+$/'];

    /** Reserved so profile URLs can't shadow app routes or impersonate the site. */
    public const RESERVED_USERNAMES = ['admin', 'envydeo', 'support', 'help', 'settings', 'feed', 'login', 'register', 'movie-night', 'api', 'about', 'staff', 'moderator', 'official'];

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'bio',
        'is_private',
        'top_films',
        'notify_friend_checkins',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_private' => 'boolean',
            'top_films' => 'array',
            'notify_friend_checkins' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Rows cascade in the database, but check-in photo files live on disk: delete them explicitly.
        static::deleting(function (User $user) {
            $user->checkIns()->get()->each->deletePhoto();
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    public function providers()
    {
        return $this->hasMany(OAuthProvider::class);
    }

    public function watchlist()
    {
        return $this->hasMany(Watchlist::class);
    }

    // ---------------------------------------------------------------- social

    public function followingRelations(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followerRelations(): HasMany
    {
        return $this->hasMany(Follow::class, 'followee_id');
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function diaryEntries(): HasMany
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function avatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim((string) $this->name)))
            ->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }

    public function profileUrl(): ?string
    {
        return $this->username ? route('profile.show', $this->username) : null;
    }
}
