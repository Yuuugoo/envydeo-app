<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** follower → followee. Private accounts receive "pending" requests until accepted. */
class Follow extends Model
{
    protected $fillable = ['follower_id', 'followee_id', 'status'];

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function followee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followee_id');
    }
}
