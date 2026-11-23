<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Delete expired Movie Night rooms (and their participants/votes via cascade).
Schedule::command('model:prune', ['--model' => [\App\Models\MatchRoom::class]])->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
