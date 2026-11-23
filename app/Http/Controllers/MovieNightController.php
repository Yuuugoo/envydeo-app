<?php

namespace App\Http\Controllers;

use App\Models\MatchRoom;
use App\Models\MovieNightEvent;
use App\Services\MovieNightCards;
use App\Services\MovieNightRooms;
use App\Support\MovieNightThemes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class MovieNightController extends Controller
{
    public function index()
    {
        return view('movie-night.index', ['themes' => MovieNightThemes::all()]);
    }

    /** "Join with a code" form on the landing page. */
    public function join(Request $request)
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('code')));

        if (strlen($code) !== 6 || !MatchRoom::active()->where('code', $code)->exists()) {
            return redirect()->route('movie-night.index')
                ->withInput(['code' => $code])
                ->with('join_error', 'We couldn’t find an active room with that code.');
        }

        return redirect()->route('movie-night.show', $code);
    }

    public function show(Request $request, string $code)
    {
        $room = MatchRoom::active()->where('code', strtoupper($code))->firstOrFail();

        // Funnel stat: someone opened an invite they're not part of yet (once per session).
        $seenKey = "movie_night.opened.{$room->id}";
        $isParticipant = (bool) $room->participantFor(session(MatchRoom::sessionKey($room->id)), $request->user());
        if (!$isParticipant && !session($seenKey)) {
            session()->put($seenKey, true);
            MovieNightEvent::record('invite_opened', $room);
        }

        // "Us" rooms need an account: send guests back here after they sign in.
        if ($room->is_persistent && !$request->user()) {
            session()->put('url.intended', $request->fullUrl());
        }

        return view('movie-night.show', ['room' => $room, 'meta' => $this->previewMeta($room)]);
    }

    /** "Decide for me": instant solo deck (optionally themed) — the first movie you like wins. */
    public function quick(Request $request, MovieNightRooms $rooms)
    {
        $data = $request->validate(['theme' => ['nullable', Rule::in(MovieNightThemes::keys())]]);

        return $this->createAndRedirect($rooms, [
            'theme' => $data['theme'] ?? null,
            'start' => true,
        ], $request->user()?->name ?? 'You');
    }

    /** Watchlist Showdown: a lobby whose deck comes from everyone's watchlists. */
    public function watchlist(Request $request, MovieNightRooms $rooms)
    {
        return $this->createAndRedirect($rooms, ['source' => 'watchlist'], $request->user()->name, back: route('watchlist.index'));
    }

    /** "Us": the signed-in user's permanent two-person rooms. */
    public function us(Request $request)
    {
        $rooms = MatchRoom::where('is_persistent', true)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $request->user()->getKey()))
            ->with('participants')
            ->withCount('results')
            ->latest()
            ->get();

        return view('movie-night.us', ['rooms' => $rooms]);
    }

    public function createUs(Request $request, MovieNightRooms $rooms)
    {
        $existing = MatchRoom::where('is_persistent', true)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $request->user()->getKey()))
            ->count();

        if ($existing >= 5) {
            return redirect()->route('movie-night.us')->with('us_error', 'You can have up to 5 “Us” rooms.');
        }

        return $this->createAndRedirect($rooms, ['persistent' => true, 'start' => true], $request->user()->name, back: route('movie-night.us'));
    }

    /** Link-preview image (Open Graph) for a room link. */
    public function preview(string $code, MovieNightCards $cards)
    {
        $room = MatchRoom::active()->where('code', strtoupper($code))->firstOrFail();
        $movie = $room->status === 'matched' ? $room->candidate($room->matched_tmdb_id) : null;

        return response()->file($movie ? $cards->match($room, $movie) : $cards->invite($room), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    /** Portrait share card for a match (stories / group chats). */
    public function card(Request $request, string $code, MovieNightCards $cards)
    {
        $room = MatchRoom::active()->where('code', strtoupper($code))->firstOrFail();

        $movie = $room->is_persistent
            ? $room->results()->where('tmdb_id', (int) $request->query('movie'))->first()?->movie
            : ($room->status === 'matched' ? $room->candidate($room->matched_tmdb_id) : null);

        abort_unless($movie, 404);

        return response()->file($cards->story($room, $movie), [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="envydeo-match-' . $room->code . '.png"',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    // ------------------------------------------------------------------

    private function createAndRedirect(MovieNightRooms $rooms, array $options, string $name, ?string $back = null)
    {
        try {
            $room = $rooms->create($options, $name, request()->user());
        } catch (RuntimeException $e) {
            return redirect($back ?? route('movie-night.index'))->with('movie_night_error', $e->getMessage());
        }

        return redirect()->route('movie-night.show', $room->code);
    }

    /** Title/description/image for link previews in Messenger, Viber, WhatsApp, Discord… */
    private function previewMeta(MatchRoom $room): array
    {
        $host = $room->participants()->where('is_host', true)->value('name') ?? 'A friend';
        $movie = $room->status === 'matched' ? $room->candidate($room->matched_tmdb_id) : null;

        return [
            'title' => match (true) {
                (bool) $movie => "It’s a match: {$movie['title']} 🍿",
                $room->is_persistent => "{$host} wants to find movies you both love 💚",
                default => "{$host} invited you to movie night 🍿",
            },
            'description' => $movie
                ? 'Picked together on Envydeo Movie Night.'
                : "Room {$room->code} · Swipe on movies together. The first one everyone likes wins.",
            'image' => route('movie-night.preview', $room->code) . '?v=' . $room->updated_at?->timestamp,
            'url' => route('movie-night.show', $room->code),
        ];
    }
}
