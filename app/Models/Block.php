<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Blocking hides both people from each other everywhere and removes follows both ways. */
class Block extends Model
{
    protected $fillable = ['blocker_id', 'blocked_id'];
}
