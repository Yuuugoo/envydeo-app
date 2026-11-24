<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewLike extends Model
{
    protected $fillable = ['diary_entry_id', 'user_id'];
}
