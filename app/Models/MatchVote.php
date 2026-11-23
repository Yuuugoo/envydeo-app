<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchVote extends Model
{
    protected $fillable = ['match_room_id', 'match_participant_id', 'tmdb_id', 'liked'];

    protected function casts(): array
    {
        return ['liked' => 'boolean'];
    }
}
