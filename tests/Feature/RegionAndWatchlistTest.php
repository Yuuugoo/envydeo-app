<?php

namespace Tests\Feature;

use App\Livewire\AddWatchlist;
use App\Models\User;
use App\Models\Watchlist;
use App\Services\ViewerRegion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RegionAndWatchlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $providers = fn ($name) => ['link' => 'https://example.test', 'flatrate' => [['provider_name' => $name, 'logo_path' => null]]];
        Http::fake([
            'api.themoviedb.org/3/watch/providers/regions*' => Http::response(['results' => [
                ['iso_3166_1' => 'US', 'english_name' => 'United States of America'],
                ['iso_3166_1' => 'PH', 'english_name' => 'Philippines'],
                ['iso_3166_1' => 'GB', 'english_name' => 'United Kingdom'],
                ['iso_3166_1' => 'NG', 'english_name' => 'Nigeria'],
            ]]),
            'api.themoviedb.org/3/movie/550/watch/providers*' => Http::response(['results' => [
                'US' => $providers('US Streamer'), 'PH' => $providers('PH Streamer'), 'GB' => $providers('UK Streamer'), 'NG' => $providers('NG Streamer'),
            ]]),
            'api.themoviedb.org/3/tv/1399/watch/providers*' => Http::response(['results' => ['PH' => $providers('PH TV Streamer')]]),
            'api.themoviedb.org/3/movie/550*' => Http::response(['id' => 550, 'title' => 'Fight Club', 'poster_path' => '/p.jpg', 'backdrop_path' => '/b.jpg', 'overview' => 'Soap.', 'tagline' => '', 'release_date' => '1999-10-15', 'runtime' => 139, 'vote_average' => 8.4, 'status' => 'Released', 'budget' => 0, 'revenue' => 0, 'original_language' => 'en', 'imdb_id' => 'tt0137523', 'genres' => [], 'credits' => ['cast' => [], 'crew' => []], 'videos' => ['results' => []]]),
            'api.themoviedb.org/3/tv/1399*' => Http::response(['id' => 1399, 'name' => 'Game of Thrones', 'poster_path' => '/g.jpg', 'backdrop_path' => '/gb.jpg', 'overview' => 'Winter.', 'tagline' => '', 'genres' => [], 'seasons' => [], 'first_air_date' => '2011-04-17', 'last_air_date' => '2019-05-19', 'status' => 'Ended', 'vote_average' => 8.5, 'number_of_seasons' => 8, 'number_of_episodes' => 73, 'original_language' => 'en', 'created_by' => [], 'networks' => [], 'credits' => ['cast' => [], 'crew' => []], 'videos' => ['results' => []], 'images' => ['backdrops' => []], 'external_ids' => ['imdb_id' => 'tt0944947']]),
            'api.themoviedb.org/*' => Http::response(['results' => [], 'genres' => []]),
        ]);
    }

    // ------------------------------------------------------------------ where to watch: country

    public function test_country_comes_from_cloudflare_header_behind_a_trusted_proxy(): void
    {
        // Tests come from 127.0.0.1, which is a trusted proxy (cloudflared / reverse proxy).
        $this->get('/movies/550', ['CF-IPCountry' => 'PH', 'Accept-Language' => 'en-US'])
            ->assertOk()->assertSee('Where to watch in Philippines')->assertSee('PH Streamer')->assertDontSee('US Streamer');
    }

    public function test_browser_language_is_the_fallback_and_us_the_last_resort(): void
    {
        $this->get('/movies/550', ['Accept-Language' => 'en-GB'])->assertSee('Where to watch in United Kingdom');
        $this->get('/movies/550', ['Accept-Language' => 'fr'])->assertSee('Where to watch in United States of America');
    }

    public function test_a_picked_country_wins_and_auto_clears_it(): void
    {
        $this->from('/movies/550')->post('/region', ['region' => 'ng'])
            ->assertRedirect()->assertCookie(ViewerRegion::COOKIE, 'NG');

        $this->withCookie(ViewerRegion::COOKIE, 'NG')
            ->get('/movies/550', ['CF-IPCountry' => 'PH'])
            ->assertSee('Where to watch in Nigeria')->assertSee('NG Streamer')
            ->assertSee('<option value="NG" selected', false); // picker shows the choice

        $this->from('/movies/550')->post('/region', ['region' => 'auto'])->assertCookieExpired(ViewerRegion::COOKIE);
        $this->from('/movies/550')->post('/region', ['region' => 'XX'])->assertCookieExpired(ViewerRegion::COOKIE); // unknown → auto
    }

    public function test_private_ips_have_no_country(): void
    {
        $regions = app(ViewerRegion::class);
        $this->assertNull($regions->countryForIp('127.0.0.1'));
        $this->assertNull($regions->countryForIp('192.168.1.20'));
        $this->assertNull($regions->countryForIp('not-an-ip'));
    }

    public function test_tv_pages_use_the_same_country(): void
    {
        $this->get('/shows/1399', ['CF-IPCountry' => 'PH'])->assertOk()->assertSee('PH TV Streamer');
    }

    // ------------------------------------------------------------------ TV watchlist

    public function test_tv_shows_can_be_added_to_the_watchlist_separately_from_movies(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/shows/1399')->assertOk()->assertSee('wire:click="toggleWatchlist"', false);

        Livewire::actingAs($user)->test(AddWatchlist::class, ['tmdbId' => 1399, 'type' => 'tv'])
            ->call('toggleWatchlist')->assertSet('isInWatchlist', true);
        // Same id as a movie is a different item.
        Livewire::actingAs($user)->test(AddWatchlist::class, ['tmdbId' => 1399, 'type' => 'movie'])->assertSet('isInWatchlist', false);

        $this->assertSame('tv', Watchlist::firstOrFail()->media_type);
        $this->actingAs($user)->get('/watchlist')->assertOk()->assertSee('Game of Thrones')->assertSee(route('show.show', 1399));

        Livewire::actingAs($user)->test(AddWatchlist::class, ['tmdbId' => 1399, 'type' => 'tv'])
            ->call('toggleWatchlist')->assertSet('isInWatchlist', false);
        $this->assertSame(0, Watchlist::count());
    }

    public function test_movie_night_watchlist_decks_ignore_tv_shows(): void
    {
        $user = User::factory()->create();
        Watchlist::create(['user_id' => $user->id, 'tmdb_id' => 1399, 'media_type' => 'tv']);

        $this->actingAs($user)->post('/movie-night/watchlist')
            ->assertRedirect(route('watchlist.index'))->assertSessionHas('movie_night_error'); // "Your watchlist is empty" (no movies)
    }
}
