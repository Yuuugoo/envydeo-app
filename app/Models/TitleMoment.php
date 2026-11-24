<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Anonymous per-minute reaction totals for a title across every Watch Along
 * (no party, member or user ids). Powers "Envydeo moments" on title pages.
 */
class TitleMoment extends Model
{
    public $timestamps = false;

    protected $fillable = ['tmdb_id', 'media_type', 'minute', 'kind', 'count'];

    public static function bump(int $tmdbId, string $type, int $minute, string $kind): void
    {
        $key = ['tmdb_id' => $tmdbId, 'media_type' => $type, 'minute' => min($minute, 65535), 'kind' => $kind];
        if (static::where($key)->increment('count') === 0) {
            static::insertOrIgnore($key + ['count' => 1]);
        }
    }

    /**
     * Summary for a title page, or null when there's too little data.
     * ['total' => n, 'bars' => [[minute, count, kind]...], 'peaks' => [[kind, minute, count]...]]
     */
    public static function summary(int $tmdbId, string $type, int $minTotal = 10): ?array
    {
        $rows = static::where('tmdb_id', $tmdbId)->where('media_type', $type)->get(['minute', 'kind', 'count']);
        $total = (int) $rows->sum('count');
        if ($total < $minTotal) {
            return null;
        }

        $byMinute = $rows->groupBy('minute')->map(fn (Collection $g, $m) => [
            'minute' => (int) $m,
            'count' => (int) $g->sum('count'),
            'kind' => $g->sortByDesc('count')->first()->kind,
        ])->sortKeys()->values();

        // Biggest minute for each reaction kind, best first.
        $peaks = $rows->groupBy('kind')->map(fn (Collection $g, $kind) => [
            'kind' => $kind,
            'minute' => (int) $g->sortByDesc('count')->first()->minute,
            'count' => (int) $g->max('count'),
        ])->sortByDesc('count')->take(3)->values();

        return ['total' => $total, 'bars' => $byMinute->all(), 'peaks' => $peaks->all(), 'lastMinute' => (int) $byMinute->max('minute')];
    }
}
