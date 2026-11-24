<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    public const REASONS = ['spam' => 'Spam', 'harassment' => 'Harassment or bullying', 'inappropriate' => 'Inappropriate photo or content', 'spoilers' => 'Unmarked spoilers', 'other' => 'Something else'];

    protected $fillable = ['reporter_id', 'reportable_type', 'reportable_id', 'reason', 'details', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
