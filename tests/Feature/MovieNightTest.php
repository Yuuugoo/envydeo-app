<?php

namespace Tests\Feature;

use App\Livewire\MovieNight\CreateRoom;
use App\Livewire\MovieNight\Room;
use App\Models\MatchRoom;
use App\Models\MovieNightEvent;
use App\Models\User;
use App\Models\Watchlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MovieNightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $movie = fn (int $id, string $title) => [
            'id' => $id, 'title' => $title, 'poster_path' => "/p{$id}.jpg", 'backdrop_path' => "/b{$id}.jpg",
            'release_date' => '2020-01-01', 'vote_average' => 7.5, 'overview' => 'An overview.', 'genre_ids' => [28],
        ];

        // A tiny real PNG so the GD image renderer has something to draw.
        $png = imagecreatetruecolor(4, 6);
        ob_start();
        imagepng($png);
        $pngBytes = ob_get_clean();

        Http::fake([
            'image.tmdb.org/*' => Http::response($pngBytes, 200, ['Content-Type' => 'image/png']),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response(['genres' => [['id' => 28, 'name' => 'Action'], ['id' => 35, 'name' => 'Comedy']]]),
            'api.themoviedb.org/3/watch/providers/movie*' => Http::response(['results' => [
                ['provider_id' => 8, 'provider_name' => 'Netflix', 'logo_path' => '/n.jpg', 'display_priority' => 1],
            ]]),
            'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => [
                $movie(101, 'Alpha'), $movie(102, 'Bravo'), $movie(103, 'Charlie'),
            ]]),
            'api.themoviedb.org/3/movie/*/watch/providers*' => Http::response(['results' => ['US' => [
                'link' => 'https://www.themoviedb.org/movie/101/watch',
                'flatrate' => [['provider_name' => 'Netflix', 'logo_path' => '/n.jpg']],
            ]]]),
            'api.themoviedb.org/3/movie/*' => function ($request) use ($movie) {
                $id = (int) last(explode('/', parse_url($request->url(), PHP_URL_PATH)));

                return Http::response($movie($id, "Saved {$id}") + ['genres' => [['id' => 35, 'name' => 'Comedy']]]);
            },
        ]);
    }

    private function makeRoom(string $status = 'lobby', bool $persistent = false): array
    {
        $room = MatchRoom::create([
            'code' => MatchRoom::newCode(),
            'status' => $status,
            'source' => 'discover',
            'is_persistent' => $persistent,
            'filters' => ['genres' => [], 'region' => 'US', 'providers' => []],
            'candidates' => [
                ['id' => 101, 'title' => 'Alpha', 'poster_path' => '/p.jpg', 'backdrop_path' => '/b.jpg', 'year' => '2020', 'rating' => 7.5, 'overview' => '', 'genre_ids' => []],
                ['id' => 102, 'title' => 'Bravo', 'poster_path' => '/p.jpg', 'backdrop_path' => null, 'year' => '2020', 'rating' => 7.5, 'overview' => '', 'genre_ids' => []],
            ],
            'started_at' => $status === 'voting' ? now() : null,
            'expires_at' => $persistent ? null : now()->addDay(),
        ]);
        [, $hostToken] = $room->addParticipant('Host', isHost: true);
        [, $guestToken] = $room->addParticipant('Guest');

        return [$room, $hostToken, $guestToken];
    }

    private function actAs(MatchRoom $room, string $token): void
    {
        session()->put(MatchRoom::sessionKey($room->id), $token);
    }

    // ------------------------------------------------------------------ core flow

    public function test_landing_page_renders(): void
    {
        $this->get('/movie-night')->assertOk()
            ->assertSee('Start a movie night')->assertSee('Netflix')
            ->assertSee('Decide for me')->assertSee('Date Night')->assertSee('Watchlist Showdown');
    }

    public function test_creating_a_room_deals_a_deck_and_makes_you_host(): void
    {
        Livewire::test(CreateRoom::class)
            ->set('name', 'Ana')
            ->set('genres', ['28'])
            ->set('providers', ['8'])
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        $room = MatchRoom::firstOrFail();
        $this->assertSame('lobby', $room->status);
        $this->assertCount(3, $room->candidates);
        $this->assertSame([28], $room->filters['genres']);
        $this->assertTrue($room->participants()->first()->is_host);
        $this->assertNotNull(session(MatchRoom::sessionKey($room->id)));
        $this->assertSame(1, MovieNightEvent::where('event', 'room_created')->count());

        Http::assertSent(fn ($request) => str_contains($request->url(), 'with_watch_providers=8'));
    }

    public function test_room_requires_a_name_and_valid_filters(): void
    {
        Livewire::test(CreateRoom::class)
            ->set('name', '')
            ->set('genres', ['99999'])
            ->set('theme', 'not-a-theme')
            ->call('create')
            ->assertHasErrors(['name', 'genres.0', 'theme']);

        $this->assertSame(0, MatchRoom::count());
    }

    public function test_it_is_a_match_only_when_everyone_likes_the_same_movie(): void
    {
        [$room, $hostToken, $guestToken] = $this->makeRoom('voting');
        $component = Livewire::test(Room::class, ['room' => $room]);

        $this->actAs($room, $hostToken);
        $this->assertFalse($component->call('vote', 101, true)->effects['returns'][0]['matched']);
        $component->call('vote', 102, true);

        $this->actAs($room, $guestToken);
        $component->call('vote', 102, false); // guest rejects Bravo
        $this->assertSame('voting', $room->fresh()->status);

        $result = $component->call('vote', 101, true)->effects['returns'][0]; // guest likes Alpha too
        $this->assertTrue($result['matched']);
        $this->assertSame('matched', $room->fresh()->status);
        $this->assertSame(101, (int) $room->fresh()->matched_tmdb_id);
        $this->assertSame(1, $room->results()->count());
        $this->assertSame(1, MovieNightEvent::where('event', 'matched')->count());

        $component->call('$refresh')
            ->assertSee('It’s a match!')
            ->assertSee('Alpha')
            ->assertSee('Where to watch')
            ->assertDontSee('vidfast', false)
            ->assertSee('Share the moment');
    }

    public function test_only_the_host_can_start_or_pick(): void
    {
        [$room, $hostToken, $guestToken] = $this->makeRoom();
        $component = Livewire::test(Room::class, ['room' => $room]);

        $this->actAs($room, $guestToken);
        $component->call('start');
        $this->assertSame('lobby', $room->fresh()->status);

        $this->actAs($room, $hostToken);
        $component->call('start');
        $this->assertSame('voting', $room->fresh()->status);
        $this->assertNotNull($room->fresh()->started_at);

        $this->actAs($room, $guestToken);
        $component->call('pick', 101);
        $this->assertSame('voting', $room->fresh()->status);
    }

    public function test_votes_for_movies_outside_the_deck_or_from_strangers_are_ignored(): void
    {
        [$room, $hostToken] = $this->makeRoom('voting');
        $component = Livewire::test(Room::class, ['room' => $room]);

        $this->actAs($room, $hostToken);
        $component->call('vote', 555, true);

        session()->forget(MatchRoom::sessionKey($room->id)); // not a participant
        $component->call('vote', 101, true);

        $this->assertSame(0, $room->votes()->count());
    }

    public function test_visitors_can_join_with_a_name_and_the_lobby_shows_a_qr_code(): void
    {
        [$room, $hostToken] = $this->makeRoom();

        Livewire::test(Room::class, ['room' => $room])
            ->assertSee('Host invited you')
            ->set('joinName', 'Bea')
            ->call('join')
            ->assertHasNoErrors()
            ->assertSee('Waiting for Host to start')
            ->assertSee('<svg', false)
            ->assertSee('Scan to join');

        $this->assertSame(3, $room->participants()->count());
        $this->assertSame(1, MovieNightEvent::where('event', 'joined')->count());
    }

    public function test_join_by_code_and_expired_rooms(): void
    {
        [$room] = $this->makeRoom();

        $this->get('/movie-night/join?code=' . strtolower($room->code))->assertRedirect("/movie-night/{$room->code}");
        $this->get('/movie-night/join?code=ZZZZZZ')->assertRedirect('/movie-night')->assertSessionHas('join_error');

        $room->update(['expires_at' => now()->subMinute()]);
        $this->get("/movie-night/{$room->code}")->assertNotFound();
    }

    public function test_movie_pages_point_to_legal_services_instead_of_an_embedded_player(): void
    {
        $this->get('/movies/101')
            ->assertOk()
            ->assertSee('Where to watch')
            ->assertSee('Netflix')
            ->assertSee('Availability data by JustWatch')
            ->assertDontSee('vidfast', false);

        $this->get('/watch-progress')->assertNotFound();
    }

    // ------------------------------------------------------------------ sharing

    public function test_room_links_have_rich_previews_and_count_invite_opens_once(): void
    {
        [$room] = $this->makeRoom();

        $this->get("/movie-night/{$room->code}")
            ->assertOk()
            ->assertSee('<meta property="og:title" content="Host invited you to movie night', false)
            ->assertSee("/movie-night/{$room->code}/preview.png", false);
        $this->get("/movie-night/{$room->code}");

        $this->assertSame(1, MovieNightEvent::where('event', 'invite_opened')->count());
    }

    public function test_preview_and_share_card_images_are_png(): void
    {
        [$room, $hostToken, $guestToken] = $this->makeRoom('voting');

        $invite = $this->get("/movie-night/{$room->code}/preview.png");
        $invite->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame([1200, 630], array_slice(getimagesize($invite->baseResponse->getFile()->getPathname()), 0, 2));

        $this->get("/movie-night/{$room->code}/card.png")->assertNotFound(); // no match yet

        $room->update(['status' => 'matched', 'matched_tmdb_id' => 101]);
        $card = $this->get("/movie-night/{$room->code}/card.png");
        $card->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame([1080, 1350], array_slice(getimagesize($card->baseResponse->getFile()->getPathname()), 0, 2));
    }

    // ------------------------------------------------------------------ decide for me & themes

    public function test_decide_for_me_starts_a_solo_deck_where_the_first_like_wins(): void
    {
        $this->post('/movie-night/quick', ['theme' => 'date-night'])->assertRedirect();

        $room = MatchRoom::firstOrFail();
        $this->assertSame('voting', $room->status);
        $this->assertSame('date-night', $room->theme);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'discover/movie') && str_contains(urldecode($request->url()), 'with_genres=10749|35'));

        $result = Livewire::test(Room::class, ['room' => $room])->call('vote', 102, true)->effects['returns'][0];
        $this->assertTrue($result['matched']);
        $this->assertSame('matched', $room->fresh()->status);
    }

    public function test_unknown_themes_are_rejected(): void
    {
        $this->post('/movie-night/quick', ['theme' => 'evil&with_genres=1'])->assertSessionHasErrors('theme');
        $this->assertSame(0, MatchRoom::count());
    }

    // ------------------------------------------------------------------ watchlist showdown

    public function test_watchlist_showdown_needs_an_account_and_a_watchlist(): void
    {
        $this->post('/movie-night/watchlist')->assertRedirect('/login');

        $user = User::factory()->create();
        $this->actingAs($user)->post('/movie-night/watchlist')
            ->assertRedirect(route('watchlist.index'))
            ->assertSessionHas('movie_night_error');
    }

    public function test_watchlist_showdown_deals_from_everyones_watchlists_on_start(): void
    {
        $host = User::factory()->create(['name' => 'Ana']);
        $friend = User::factory()->create(['name' => 'Ben']);
        foreach ([[$host, 201], [$host, 202], [$friend, 202], [$friend, 203]] as [$u, $id]) {
            Watchlist::create(['user_id' => $u->id, 'tmdb_id' => $id]);
        }

        $this->actingAs($host)->post('/movie-night/watchlist')->assertRedirect();
        $room = MatchRoom::firstOrFail();
        $this->assertSame('watchlist', $room->source);
        $this->assertEqualsCanonicalizing([201, 202], $room->candidateIds());

        $room->addParticipant('Ben', userId: $friend->id);
        Livewire::actingAs($host)->test(Room::class, ['room' => $room])->call('start');

        $ids = $room->fresh()->candidateIds();
        $this->assertEqualsCanonicalizing([201, 202, 203], $ids);
        $this->assertSame(202, $ids[0], 'Saved by both, so it comes first');
    }

    // ------------------------------------------------------------------ "Us" rooms

    public function test_us_rooms_collect_matches_and_keep_going(): void
    {
        $ana = User::factory()->create(['name' => 'Ana']);
        $ben = User::factory()->create(['name' => 'Ben']);

        $this->actingAs($ana)->post('/movie-night/us')->assertRedirect();
        $room = MatchRoom::firstOrFail();
        $this->assertTrue($room->is_persistent);
        $this->assertNull($room->expires_at);
        $this->assertSame('voting', $room->status);

        // Ana swipes ahead alone: no match until her partner agrees.
        $component = Livewire::actingAs($ana)->test(Room::class, ['room' => $room]);
        $this->assertFalse($component->call('vote', 101, true)->effects['returns'][0]['matched']);

        // Ben joins (on another device — recognised by account) and likes the same movie.
        $joined = Livewire::actingAs($ben)->test(Room::class, ['room' => $room])->set('joinName', 'Ben')->call('join');
        $result = $joined->call('vote', 101, true)->effects['returns'][0];

        $this->assertTrue($result['matched']);
        $this->assertSame('Alpha', $result['title']);
        $this->assertSame('voting', $room->fresh()->status, '"Us" rooms keep going after a match');
        $this->assertSame(1, $room->results()->count());

        $joined->set('tab', 'matches')->assertSee('Our matches')->assertSee('Alpha');

        $resultId = $room->results()->first()->id;
        $joined->call('toggleWatched', $resultId);
        $this->assertNotNull($room->results()->first()->watched_at);

        $this->actingAs($ana)->get('/movie-night/us')->assertOk()->assertSee('Ana &amp; Ben', false)->assertSee('1 match');
    }

    public function test_guests_cannot_join_us_rooms_and_they_hold_two_people(): void
    {
        [$room] = $this->makeRoom('voting', persistent: true);
        $room->participants()->where('is_host', false)->delete(); // just the creator so far
        session()->forget(MatchRoom::sessionKey($room->id));

        Livewire::test(Room::class, ['room' => $room])
            ->assertSee('Sign in to join')
            ->set('joinName', 'Sneaky')
            ->call('join')
            ->assertHasErrors('joinName');

        $partner = User::factory()->create();
        Livewire::actingAs($partner)->test(Room::class, ['room' => $room])
            ->set('joinName', 'Partner')->call('join')->assertHasNoErrors();
        session()->forget(MatchRoom::sessionKey($room->id));

        $third = User::factory()->create();
        Livewire::actingAs($third)->test(Room::class, ['room' => $room])
            ->assertSee('This room is full')
            ->set('joinName', 'Third')
            ->call('join')
            ->assertHasErrors('joinName');

        $this->assertSame(2, $room->participants()->count());
    }

    public function test_a_signed_in_user_never_inherits_someone_elses_session_token(): void
    {
        $ana = User::factory()->create();
        $ben = User::factory()->create();

        $this->actingAs($ana)->post('/movie-night/us'); // Ana's token is now in this browser session
        $room = MatchRoom::firstOrFail();

        // Ben signs in on the same browser: he must be asked to join, not treated as Ana.
        Livewire::actingAs($ben)->test(Room::class, ['room' => $room])->assertSee('invited you');
        $this->assertSame($ana->id, $room->participantFor(session(MatchRoom::sessionKey($room->id)), $ana)?->user_id);
        $this->assertNull($room->participantFor(session(MatchRoom::sessionKey($room->id)), $ben));
    }

    public function test_us_rooms_require_login(): void
    {
        $this->get('/movie-night/us')->assertRedirect('/login');
        $this->post('/movie-night/us')->assertRedirect('/login');
    }

    // ------------------------------------------------------------------ stats

    public function test_stats_are_only_for_admins(): void
    {
        config(['envydeo.admin_emails' => ['boss@example.com']]);
        $boss = User::factory()->create(['email' => 'boss@example.com']);
        $someone = User::factory()->create();

        [$room] = $this->makeRoom();
        MovieNightEvent::record('room_created', $room, ['kind' => 'group']);
        MovieNightEvent::record('started', $room, ['participants' => 3]);
        MovieNightEvent::record('matched', $room, ['seconds' => 95, 'participants' => 3]);

        $this->get('/movie-night/stats')->assertRedirect('/login');
        $this->actingAs($someone)->get('/movie-night/stats')->assertForbidden();
        $this->actingAs($boss)->get('/movie-night/stats')
            ->assertOk()
            ->assertSee('Match rate')
            ->assertSee('100%')
            ->assertSee('1m 35s');
    }

    public function test_analytics_events_contain_no_personal_data(): void
    {
        $user = User::factory()->create(['name' => 'Private Person', 'email' => 'private@example.com']);
        $this->actingAs($user)->post('/movie-night/quick');

        $stored = MovieNightEvent::all()->toJson();
        $this->assertStringNotContainsString('Private Person', $stored);
        $this->assertStringNotContainsString('private@example.com', $stored);
        $this->assertStringNotContainsString((string) $user->id, $stored);
    }
}
