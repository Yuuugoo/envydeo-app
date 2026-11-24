<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\TitleMoment;
use App\Models\User;
use App\Models\WatchParty;
use App\Models\WatchPartyMember;
use App\Models\WatchPartyReaction;
use App\Notifications\WatchAlongInvite;
use App\Services\MovieNightDealer;
use App\Services\SocialGraph;
use App\Services\Tmdb;
use App\Services\ViewerRegion;
use App\Support\TitleSnapshot;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Watch Along: everyone presses play on their own service at the same moment; Envydeo keeps
 * the shared clock (polled every ~1.5s) and pins reactions to the second they happened.
 */
class WatchAlongController extends Controller
{
    // ------------------------------------------------------------------ create & join

    public function store(Request $request, Tmdb $tmdb)
    {
        $data = $request->validate([
            'tmdb_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', Rule::in(['movie', 'tv'])],
            'season' => ['nullable', 'integer', 'between:0,200'],
            'episode' => ['nullable', 'integer', 'between:1,2000'],
        ]);
        $user = $request->user();

        if (RateLimiter::tooManyAttempts('watch-along:' . $user->id, 10)) {
            return back()->with('watch_along_error', 'You’ve started a lot of Watch Alongs. Try again in a bit.');
        }
        RateLimiter::hit('watch-along:' . $user->id, 3600);

        $snapshot = TitleSnapshot::fetch((int) $data['tmdb_id'], $data['type']);
        abort_unless($snapshot, 404);

        $season = $data['type'] === 'tv' ? (int) ($data['season'] ?? 1) : null;
        $episode = $data['type'] === 'tv' ? (int) ($data['episode'] ?? 1) : null;

        $party = WatchParty::create([
            'code' => WatchParty::newCode(),
            'tmdb_id' => $snapshot['id'],
            'media_type' => $data['type'],
            'season' => $season,
            'episode' => $episode,
            'movie' => $snapshot,
            'runtime_seconds' => $this->runtimeSeconds($tmdb, $data['type'], $snapshot['id'], $season, $episode),
            'status' => 'lobby',
            'expires_at' => now()->addHours(WatchParty::LIFETIME_HOURS),
        ]);

        [, $token] = $party->addMember($user->name, true, $user->id);
        $request->session()->put(WatchParty::sessionKey($party->id), $token);

        return redirect()->route('watch-along.show', $party->code);
    }

    public function show(Request $request, string $code, ViewerRegion $regions, MovieNightDealer $dealer, SocialGraph $graph)
    {
        $party = $this->party($code);
        $me = $this->me($request, $party);
        $members = $party->members()->orderByDesc('is_host')->orderBy('id')->get();

        $region = $regions->resolve($request);

        return view('watch-along.show', [
            'party' => $party,
            'me' => $me,
            'members' => $members,
            'expired' => $party->expires_at->isPast(),
            'whereToWatch' => $party->status === 'lobby' ? $dealer->whereToWatch($party->tmdb_id, $region, $party->media_type) : null,
            'region' => $region,
            'regions' => $regions->available(),
            'qrSvg' => $me && $party->status === 'lobby' ? $this->qrSvg(route('watch-along.show', $party->code)) : null,
            'invitable' => $me ? $this->invitableFriends($request->user(), $party, $members, $graph) : collect(),
            'summary' => $me && $party->isOver() ? $this->summary($party) : null,
            'initialState' => $me ? $this->statePayload($party, $me, 0) : null,
        ]);
    }

    public function join(Request $request, string $code)
    {
        $party = $this->party($code);
        abort_if($party->isOver() || $party->expires_at->isPast(), 410, 'This Watch Along has ended.');

        if ($this->me($request, $party)) {
            return redirect()->route('watch-along.show', $party->code);
        }

        $user = $request->user();
        $name = $user?->name ?? $request->validate(['name' => ['required', 'string', 'max:40']])['name'];

        if ($party->members()->count() >= WatchParty::MAX_MEMBERS) {
            return back()->withErrors(['name' => 'This Watch Along is full.']);
        }

        [, $token] = $party->addMember($name, false, $user?->id);
        $request->session()->put(WatchParty::sessionKey($party->id), $token);

        return redirect()->route('watch-along.show', $party->code);
    }

    // ------------------------------------------------------------------ live (JSON)

    public function state(Request $request, string $code): JsonResponse
    {
        $party = $this->party($code);
        $me = $this->me($request, $party);
        abort_unless($me, 403);

        if (!$me->last_seen_at || $me->last_seen_at->lt(now()->subSeconds(10))) {
            $me->forceFill(['last_seen_at' => now()])->save();
        }
        if ($party->shouldAutoEnd()) {
            $this->end($party, null);
        }

        return response()->json($this->statePayload($party->fresh(), $me, (int) $request->query('after', 0)));
    }

    public function react(Request $request, string $code): JsonResponse
    {
        $party = $this->party($code);
        $me = $this->me($request, $party);
        abort_unless($me, 403);
        abort_unless(in_array($party->status, ['playing', 'paused'], true), 409);

        $data = $request->validate([
            'kind' => ['required', Rule::in([...array_keys(WatchPartyReaction::KINDS), 'text'])],
            'body' => ['nullable', 'required_if:kind,text', 'string', 'max:80'],
        ]);

        $key = 'watch-react:' . $me->id;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 20);

        $position = $party->positionSeconds();
        $reaction = $party->reactions()->create([
            'watch_party_member_id' => $me->id,
            'kind' => $data['kind'],
            'body' => $data['kind'] === 'text' ? trim($data['body']) : null,
            'position_s' => $position,
        ]);

        if ($data['kind'] !== 'text') {
            TitleMoment::bump($party->tmdb_id, $party->media_type, intdiv($position, 60), $data['kind']);
        }

        return response()->json($this->reactionPayload($reaction->setRelation('member', $me), $me));
    }

    public function control(Request $request, string $code): JsonResponse
    {
        $party = $this->party($code);
        $me = $this->me($request, $party);
        abort_unless($me, 403);

        $data = $request->validate([
            'action' => ['required', Rule::in(['start', 'pause', 'resume', 'shift', 'end'])],
            'seconds' => ['nullable', 'integer', 'between:-3600,3600'],
        ]);
        $hostOnly = ['start', 'shift', 'end'];
        abort_if(in_array($data['action'], $hostOnly, true) && !$me->is_host, 403);

        DB::transaction(function () use ($party, $me, $data) {
            $party = WatchParty::whereKey($party->id)->lockForUpdate()->first();
            $now = WatchParty::nowMs();
            $first = explode(' ', $me->name)[0];

            switch ($data['action']) {
                case 'start':
                    if ($party->status === 'lobby') {
                        $party->fill(['status' => 'playing', 'base_ms' => 0, 'resumed_at_ms' => $now + WatchParty::COUNTDOWN_MS]);
                        $this->event($party, "{$first} started the countdown", $now);
                    }
                    break;
                case 'pause':
                    if ($party->status === 'playing') {
                        $party->fill(['status' => 'paused', 'base_ms' => max(0, $party->positionMs($now)), 'resumed_at_ms' => null]);
                        $this->event($party, "{$first} paused at " . WatchParty::formatTime($party->positionSeconds($now)), $now);
                    }
                    break;
                case 'resume':
                    if ($party->status === 'paused') {
                        $party->fill(['status' => 'playing', 'resumed_at_ms' => $now + WatchParty::RESUME_COUNTDOWN_MS]);
                        $this->event($party, "{$first} hit play", $now);
                    }
                    break;
                case 'shift':
                    if (in_array($party->status, ['playing', 'paused'], true)) {
                        $delta = (int) ($data['seconds'] ?? 0) * 1000;
                        $position = $party->positionMs($now);
                        $delta = max($delta, -max(0, $position)); // never before 0:00
                        $party->base_ms = max(0, $party->base_ms + $delta);
                        $this->event($party, "{$first} moved everyone to " . WatchParty::formatTime($party->positionSeconds($now)), $now);
                    }
                    break;
                case 'end':
                    $this->end($party, $first);
                    break;
            }
            $party->save();
        });

        return response()->json($this->statePayload($party->fresh(), $me, (int) $request->input('after', 0)));
    }

    public function invite(Request $request, string $code, SocialGraph $graph): JsonResponse
    {
        $party = $this->party($code);
        $me = $this->me($request, $party);
        $viewer = $request->user();
        abort_unless($me && $viewer?->username && !$party->isOver(), 403);

        $friend = User::whereNotNull('username')->findOrFail($request->validate(['user_id' => ['required', 'string']])['user_id']);
        abort_unless($graph->areFriends($viewer, $friend), 403);

        if (Cache::add("watch-invite:{$party->id}:{$friend->id}", true, 3600)) {
            $friend->notify(new WatchAlongInvite($viewer, $party));
        }

        return response()->json(['invited' => true]);
    }

    // ------------------------------------------------------------------ helpers

    private function party(string $code): WatchParty
    {
        return WatchParty::where('code', strtoupper($code))->firstOrFail();
    }

    private function me(Request $request, WatchParty $party): ?WatchPartyMember
    {
        return $party->memberFor($request->session()->get(WatchParty::sessionKey($party->id)), $request->user());
    }

    private function event(WatchParty $party, string $text, int $nowMs): void
    {
        $party->last_event = mb_substr($text, 0, 80);
        $party->last_event_at_ms = $nowMs;
    }

    /** Ends the party and adds it to signed-in members' friends feeds (if they watched a while). */
    private function end(WatchParty $party, ?string $by): void
    {
        if ($party->isOver()) {
            return;
        }
        $now = WatchParty::nowMs();
        $watched = $party->positionSeconds($now);
        $party->fill(['status' => 'ended', 'base_ms' => max(0, $party->positionMs($now)), 'resumed_at_ms' => null, 'ended_at' => now()]);
        $this->event($party, $by ? "{$by} ended the Watch Along" : 'The Watch Along ended', $now);
        $party->save();

        if ($watched < 300) {
            return; // under 5 minutes: not worth a feed post
        }
        $accounts = $party->members()->whereNotNull('user_id')->with('user')->get()->pluck('user')->filter(fn ($u) => $u?->username);
        $counts = $party->reactions()->where('kind', '!=', 'text')->selectRaw('kind, count(*) as n')->groupBy('kind')->pluck('n', 'kind')->map(fn ($n) => (int) $n)->sortDesc()->all();

        foreach ($accounts as $user) {
            Activity::record($user, 'watched_along', null, [
                'movie' => $party->movie,
                'title' => $party->title(),
                'with' => $accounts->reject(fn ($u) => $u->is($user))->map(fn ($u) => ['name' => $u->name, 'username' => $u->username])->values()->all(),
                'guests' => $party->members()->whereNull('user_id')->count(),
                'reactions' => $counts,
            ], 'friends');
        }
    }

    private function statePayload(WatchParty $party, WatchPartyMember $me, int $after): array
    {
        // First load: the latest 40. After that: everything newer than the last id the client has.
        $reactions = $party->reactions()->with('member')->where('id', '>', $after)
            ->orderBy('id', $after === 0 ? 'desc' : 'asc')->limit($after === 0 ? 40 : 200)
            ->get()->sortBy('id')->values();

        return [
            'status' => $party->status,
            'serverNow' => WatchParty::nowMs(),
            'baseMs' => $party->base_ms,
            'resumedAtMs' => $party->resumed_at_ms,
            'runtime' => $party->runtime_seconds,
            'lastEvent' => $party->last_event,
            'lastEventAt' => $party->last_event_at_ms,
            'members' => $party->members()->orderByDesc('is_host')->orderBy('id')->get()->map(fn ($m) => [
                'id' => $m->id, 'name' => $m->name, 'initials' => $m->initials(), 'host' => $m->is_host,
                'me' => $m->id === $me->id, 'here' => $m->id === $me->id || $m->isHere(),
            ])->all(),
            'reactions' => $reactions->map(fn ($r) => $this->reactionPayload($r, $me))->all(),
        ];
    }

    private function reactionPayload(WatchPartyReaction $r, WatchPartyMember $me): array
    {
        return [
            'id' => $r->id, 'kind' => $r->kind, 'emoji' => $r->emoji(), 'body' => $r->body,
            'pos' => $r->position_s, 'name' => $r->member?->name ?? 'Someone', 'mine' => $r->watch_party_member_id === $me->id,
        ];
    }

    /**
     * Moment map for the end screen: reaction bars across the runtime, the biggest moments
     * (with who reacted) and a full replay.
     */
    private function summary(WatchParty $party): array
    {
        $reactions = $party->reactions()->with('member')->orderBy('position_s')->orderBy('id')->get();
        $emoji = $reactions->where('kind', '!=', 'text');
        $length = max($party->runtime_seconds, (int) $reactions->max('position_s'), 60);
        $bucket = max(60, (int) ceil($length / 80 / 60) * 60); // at most ~80 bars

        $bars = collect(range(0, intdiv($length, $bucket)))->map(function ($i) use ($emoji, $bucket) {
            $in = $emoji->filter(fn ($r) => intdiv($r->position_s, $bucket) === $i);

            return ['start' => $i * $bucket, 'count' => $in->count(), 'kind' => $in->countBy('kind')->sortDesc()->keys()->first()];
        });

        $moments = $bars->where('count', '>', 0)->sortByDesc('count')->take(3)->map(function ($bar) use ($emoji, $bucket) {
            $in = $emoji->filter(fn ($r) => $r->position_s >= $bar['start'] && $r->position_s < $bar['start'] + $bucket);

            return $bar + [
                'emoji' => WatchPartyReaction::KINDS[$bar['kind']][0] ?? '✨',
                'word' => WatchPartyReaction::KINDS[$bar['kind']][1] ?? 'reacted',
                'names' => $in->map(fn ($r) => explode(' ', $r->member?->name ?? '')[0])->unique()->values()->all(),
            ];
        })->values();

        return [
            'bars' => $bars->all(),
            'max' => max(1, (int) $bars->max('count')),
            'moments' => $moments->all(),
            'counts' => $emoji->countBy('kind')->sortDesc()->all(),
            'replay' => $reactions,
            'watched' => $party->positionSeconds(),
        ];
    }

    private function runtimeSeconds(Tmdb $tmdb, string $type, int $id, ?int $season, ?int $episode): int
    {
        if ($type === 'tv') {
            $ep = $tmdb->get("/tv/{$id}/season/{$season}/episode/{$episode}", [], Tmdb::DAY);
            $minutes = $ep['runtime'] ?? null;
            if (!$minutes) {
                $minutes = $tmdb->get("/tv/{$id}", [], Tmdb::DAY)['episode_run_time'][0] ?? 45;
            }
        } else {
            $minutes = $tmdb->get("/movie/{$id}", [], Tmdb::DAY)['runtime'] ?? 0;
        }

        return max(10, (int) ($minutes ?: 120)) * 60;
    }

    private function invitableFriends(?User $viewer, WatchParty $party, Collection $members, SocialGraph $graph): Collection
    {
        if (!$viewer?->username || $party->isOver() || $members->count() >= WatchParty::MAX_MEMBERS) {
            return collect();
        }
        $inParty = $members->pluck('user_id')->filter()->all();

        return User::whereIn('id', array_diff($graph->friendIds($viewer), $inParty))->whereNotNull('username')->orderBy('name')->limit(20)->get();
    }

    private function qrSvg(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd()));

        return trim(preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($url)));
    }
}
