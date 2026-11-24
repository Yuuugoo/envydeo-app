<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A movie or TV show a user wants to watch (media_type: movie | tv). */
class Watchlist extends Model
{
    protected $table = 'watchlist';

    protected $fillable = ['user_id', 'tmdb_id', 'media_type'];

    protected $attributes = ['media_type' => 'movie'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMovies(Builder $query): Builder
    {
        return $query->where('media_type', 'movie');
    }

    /** TMDB detail path for this item, e.g. /tv/1399 */
    public function tmdbPath(): string
    {
        return '/' . ($this->media_type === 'tv' ? 'tv' : 'movie') . '/' . $this->tmdb_id;
    }
}
