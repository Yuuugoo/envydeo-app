<?php

namespace App\Http\Controllers;

use App\Models\MovieNightEvent;
use App\Support\MovieNightThemes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Internal funnel stats for Movie Night (admins only, see ADMIN_EMAILS). */
class MovieNightStatsController extends Controller
{
    public const RANGES = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    public function __invoke(Request $request)
    {
        $days = array_key_exists((int) $request->query('days'), self::RANGES) ? (int) $request->query('days') : 30;
        $events = MovieNightEvent::where('created_at', '>=', now()->subDays($days))->get(['event', 'match_room_id', 'meta', 'created_at']);
        $by = fn (string $event) => $events->where('event', $event);

        $created = $by('room_created');
        $started = $by('started')->where('meta.round', '!=', 'again');
        $matched = $by('matched');
        $groupStarts = $started->filter(fn ($e) => ($e->meta['participants'] ?? 1) >= 2);
        $startedRooms = $started->pluck('match_room_id')->unique();
        $matchedRooms = $matched->pluck('match_room_id')->unique()->intersect($startedRooms);
        $seconds = $matched->pluck('meta.seconds')->filter()->sort()->values();

        $funnel = [
            ['label' => 'Rooms created', 'value' => $created->count()],
            ['label' => 'Rooms started', 'value' => $startedRooms->count()],
            ['label' => 'Rooms that matched', 'value' => $matchedRooms->count()],
        ];

        return view('movie-night.stats', [
            'days' => $days,
            'ranges' => self::RANGES,
            'funnel' => $funnel,
            'kpis' => [
                ['label' => 'Match rate', 'value' => $this->pct($matchedRooms->count(), $startedRooms->count()), 'hint' => 'Started rooms that ended in a match'],
                ['label' => 'Invite → join', 'value' => $this->pct($by('joined')->count(), $by('invite_opened')->count()), 'hint' => 'Invite links opened that turned into a join'],
                ['label' => 'Avg. people per group', 'value' => $groupStarts->isEmpty() ? '—' : number_format($groupStarts->avg(fn ($e) => $e->meta['participants']), 1), 'hint' => 'Rooms started with 2+ people'],
                ['label' => 'Median time to match', 'value' => $this->duration($this->median($seconds)), 'hint' => 'From start to the match'],
            ],
            'kinds' => $this->countBy($created, 'kind', ['group' => 'Group rooms', 'solo' => 'Decide for me', 'us' => '“Us” rooms']),
            'themes' => $this->countBy($created->filter(fn ($e) => !empty($e->meta['theme'])), 'theme',
                collect(MovieNightThemes::THEMES)->map(fn ($t) => $t['name'])->all()),
            'sources' => $this->countBy($created, 'source', ['discover' => 'Discover', 'watchlist' => 'Watchlist Showdown']),
            'shares' => [
                'invites' => $this->countBy($by('invite_shared'), 'channel', ['copy' => 'Copied link', 'native' => 'Share sheet', 'qr' => 'QR code']),
                'cards' => $this->countBy($by('card_shared'), 'channel', ['native' => 'Share sheet', 'download' => 'Downloaded']),
            ],
            'daily' => collect(range($days - 1, 0))->map(function ($ago) use ($created, $matched) {
                $day = now()->subDays($ago)->toDateString();

                return [
                    'date' => now()->subDays($ago),
                    'created' => $created->filter(fn ($e) => $e->created_at->toDateString() === $day)->count(),
                    'matched' => $matched->filter(fn ($e) => $e->created_at->toDateString() === $day)->count(),
                ];
            })->take(-14)->values(),
        ]);
    }

    private function countBy(Collection $events, string $key, array $labels): array
    {
        $counts = $events->countBy(fn ($e) => $e->meta[$key] ?? null);

        return collect($labels)
            ->map(fn ($label, $value) => ['label' => $label, 'value' => $counts[$value] ?? 0])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    private function pct(int $part, int $whole): string
    {
        return $whole ? round($part / $whole * 100) . '%' : '—';
    }

    private function median(Collection $sorted): ?float
    {
        $n = $sorted->count();
        if ($n === 0) {
            return null;
        }

        return $n % 2 ? (float) $sorted[intdiv($n, 2)] : ($sorted[$n / 2 - 1] + $sorted[$n / 2]) / 2;
    }

    private function duration(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        return $seconds < 60 ? round($seconds) . 's' : floor($seconds / 60) . 'm ' . str_pad((string) round(fmod($seconds, 60)), 2, '0', STR_PAD_LEFT) . 's';
    }
}
