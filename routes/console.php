<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Delete expired Movie Night rooms and Watch Alongs (members, votes and reactions go with them via cascade).
Schedule::command('model:prune', ['--model' => [\App\Models\MatchRoom::class, \App\Models\WatchParty::class]])->daily();

// Offline IP → country database for "Where to watch" (DB-IP Lite, CC BY 4.0, refreshed monthly).
Artisan::command('envydeo:geoip', function () {
    $target = storage_path('app/' . \App\Services\ViewerRegion::GEOIP_PATH);
    @mkdir(dirname($target), 0755, true);

    foreach ([now(), now()->subMonthNoOverflow()] as $month) {
        $url = 'https://download.db-ip.com/free/dbip-country-lite-' . $month->format('Y-m') . '.mmdb.gz';
        $response = \Illuminate\Support\Facades\Http::timeout(120)->get($url);
        if (!$response->successful()) {
            continue;
        }
        $data = gzdecode($response->body());
        if ($data === false || strlen($data) < 1_000_000) {
            continue;
        }
        file_put_contents($target . '.tmp', $data);
        rename($target . '.tmp', $target);
        $this->info('Saved ' . basename($url, '.gz') . ' (' . round(strlen($data) / 1048576, 1) . ' MB)');

        return 0;
    }
    $this->error('Could not download the DB-IP Lite country database.');

    return 1;
})->purpose('Download the offline IP-to-country database used for "Where to watch"');

Schedule::command('envydeo:geoip')->monthlyOn(3, '04:20');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
