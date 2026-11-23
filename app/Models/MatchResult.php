<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A movie every participant liked. "Us" rooms collect these into "Our matches". */
class MatchResult extends Model
{
    protected $fillable = ['match_room_id', 'tmdb_id', 'movie', 'watched_at'];

    protected function casts(): array
    {
        return ['movie' => 'array', 'watched_at' => 'datetime'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(MatchRoom::class, 'match_room_id');
    }
}
