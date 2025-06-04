<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchProgress extends Model
{
    
    protected $table = 'watch_progress';

    protected $fillable = [
        'user_id',
        'tmdb_id',
        'media_type',
        'watched',
        'duration',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getWatchedPercentageAttribute()
    {
        return $this->duration > 0 ? ($this->watched / $this->duration) * 100 : 0;
    }

}
