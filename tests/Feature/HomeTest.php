<?php

namespace Tests\Feature;

use App\Livewire\Social\TonightHome;
use App\Models\Follow;
use App\Models\MatchRoom;
use App\Models\MovieNightEvent;
use App\Models\User;
use App\Models\Watchlist;
use App\Notifications\MovieNightInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $movie = fn (int $id) => ['id' => $id, 'title' => "Movie {$id}", 'poster_path' => "/p{$id}.jpg", 'backdrop_path' => "/b{$id}.jpg",
            'release_date' => '2021-05-01', 'vote_average' => 7.1, 'vote_count' => 900, 'genre_ids' => [28], 'runtime' => 101];
        Http::fake([
            'api.themoviedb.org/3/trending/movie/week*' => Http::response(['results' => array_map($movie, range(1, 14))]),
            'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => array_map($movie, [1, 2, 3])]),
            'api.themoviedb.org/3/movie/*' => fn ($r) => Http::response($movie((int) last(explode('/', parse_url($r->url(), PHP_URL_PATH))))),
            'api.themoviedb.org/*' => Http::response(['genres' => []]),
        ]);
    }

    private function user(string $username): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username]);
    }

    public function test_visitors_get_the_movie_night_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Pick tonight’s movie together', false)
            ->assertSee('swipeDeck(', false)        // playable demo deck
            ->assertSee('Movie 1')                  // dealt from trending
            ->assertSee('What’s the mood?', false)
            ->assertDontSee('heroCarousel')         // the streaming-style carousel is gone
            ->assertDontSee('Details &amp; where to watch', false);
    }

    public function test_landing_page_shows_most_matched_movies_from_anonymous_events(): void
    {
        foreach ([107, 107, 107, 108, 108, 109, 110] as $id) {
            MovieNightEvent::create(['event' => 'matched', 'meta' => ['how' => 'unanimous', 'tmdb_id' => $id]]);
        }
        $old = MovieNightEvent::create(['event' => 'matched', 'meta' => ['tmdb_id' => 111]]); // too old
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Most matched this week', 'Movie 107', '3 matches', 'Movie 108', '2 matches'])
            ->assertDontSee('Movie 111');
    }

    public function test_signed_in_users_get_tonight_with_overlaps_and_can_start_a_night(): void
    {
        Notification::fake();
        [$ana, $ben, $cara] = [$this->user('ana'), $this->user('ben'), $this->user('cara')];
        Follow::create(['follower_id' => $ana->id, 'followee_id' => $ben->id, 'status' => 'accepted']);
        foreach ([[$ana, 42], [$ben, 42], [$cara, 42], [$ana, 43]] as [$u, $id]) {
            Watchlist::create(['user_id' => $u->id, 'tmdb_id' => $id]);
        }

        $this->actingAs($ana)->get('/')->assertOk()
            ->assertSee('What’s it going to be tonight?', false)
            ->assertSee('You both want to watch')
            ->assertSee('Movie 42')
            ->assertDontSee('Movie 43'); // nobody Ana follows saved it (Cara isn't followed)

        Livewire::actingAs($ana)->test(TonightHome::class)->call('startNight', 43)->assertNoRedirect(); // no overlap → nothing
        Livewire::actingAs($ana)->test(TonightHome::class)->call('startNight', 42)->assertRedirect();

        $room = MatchRoom::firstOrFail();
        $this->assertSame('watchlist', $room->source);
        Notification::assertSentTo($ben, MovieNightInvite::class);
        Notification::assertNotSentTo($cara, MovieNightInvite::class);
    }

    public function test_signed_in_users_without_a_profile_still_get_the_landing_page(): void
    {
        $this->actingAs(User::factory()->create(['username' => null]))->get('/')
            ->assertOk()->assertSee('Set up your profile');
    }
}
