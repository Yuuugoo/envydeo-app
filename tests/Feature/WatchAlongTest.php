<?php

namespace Tests\Feature;

use App\Livewire\Social\TitleSocial;
use App\Models\Activity;
use App\Models\Follow;
use App\Models\TitleMoment;
use App\Models\User;
use App\Models\WatchParty;
use App\Notifications\WatchAlongInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class WatchAlongTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake([
            'api.themoviedb.org/3/movie/550*' => Http::response(['id' => 550, 'title' => 'Fight Club', 'poster_path' => '/p.jpg', 'backdrop_path' => '/b.jpg', 'release_date' => '1999-10-15', 'runtime' => 139]),
            'api.themoviedb.org/3/tv/1399/season/2/episode/3*' => Http::response(['id' => 9, 'runtime' => 52]),
            'api.themoviedb.org/3/tv/1399*' => Http::response(['id' => 1399, 'name' => 'Game of Thrones', 'poster_path' => '/g.jpg', 'first_air_date' => '2011-04-17', 'episode_run_time' => [60]]),
            'api.themoviedb.org/*' => Http::response(['results' => []]),
        ]);
    }

    private function user(string $username): User
    {
        return User::factory()->create(['name' => ucfirst($username) . ' Test', 'username' => $username]);
    }

    /** Ana hosts a Fight Club Watch Along. */
    private function hostParty(User $host): WatchParty
    {
        $this->actingAs($host)->post('/watch-along', ['tmdb_id' => 550, 'type' => 'movie'])->assertRedirect();

        return WatchParty::latest('id')->firstOrFail();
    }

    /** Moves the shared clock so the title is $seconds in and playing. */
    private function playingAt(WatchParty $party, int $seconds): void
    {
        $party->update(['status' => 'playing', 'base_ms' => 0, 'resumed_at_ms' => WatchParty::nowMs() - $seconds * 1000]);
    }

    public function test_hosting_needs_an_account_and_uses_the_titles_runtime(): void
    {
        $this->post('/watch-along', ['tmdb_id' => 550, 'type' => 'movie'])->assertRedirect(route('login'));

        $party = $this->hostParty($this->user('ana'));
        $this->assertSame(['lobby', 139 * 60, 'Fight Club'], [$party->status, $party->runtime_seconds, $party->title()]);
        $this->assertTrue($party->members()->first()->is_host);

        $tv = $this->actingAs($this->user('ben'))->post('/watch-along', ['tmdb_id' => 1399, 'type' => 'tv', 'season' => 2, 'episode' => 3]);
        $show = WatchParty::where('media_type', 'tv')->firstOrFail();
        $tv->assertRedirect(route('watch-along.show', $show->code));
        $this->assertSame([52 * 60, 'Game of Thrones · S2 E3'], [$show->runtime_seconds, $show->title()]);
    }

    public function test_guests_join_with_a_name_and_outsiders_get_nothing(): void
    {
        $party = $this->hostParty($this->user('ana'));
        auth()->logout();

        $this->get(route('watch-along.show', $party->code))->assertOk()->assertSee('invited you to watch together');
        $this->getJson(route('watch-along.state', $party->code))->assertForbidden();
        $this->postJson(route('watch-along.react', $party->code), ['kind' => 'laugh'])->assertForbidden();

        $this->post(route('watch-along.join', $party->code), ['name' => 'Kim'])->assertRedirect(route('watch-along.show', $party->code));
        $this->getJson(route('watch-along.state', $party->code))->assertOk()
            ->assertJsonPath('status', 'lobby')->assertJsonCount(2, 'members')
            ->assertJsonPath('members.1.name', 'Kim')->assertJsonPath('members.1.me', true);
    }

    public function test_only_the_host_starts_and_everyone_gets_a_countdown(): void
    {
        $ana = $this->user('ana');
        $ben = $this->user('ben');
        $party = $this->hostParty($ana);
        $this->actingAs($ben)->post(route('watch-along.join', $party->code));

        $this->actingAs($ben)->postJson(route('watch-along.control', $party->code), ['action' => 'start'])->assertForbidden();
        $this->postJson(route('watch-along.react', $party->code), ['kind' => 'laugh'])->assertStatus(409); // not started yet

        $before = WatchParty::nowMs();
        $this->actingAs($ana)->postJson(route('watch-along.control', $party->code), ['action' => 'start'])
            ->assertOk()->assertJsonPath('status', 'playing')->assertJsonPath('lastEvent', 'Ana started the countdown');

        $party->refresh();
        $this->assertGreaterThanOrEqual($before + WatchParty::COUNTDOWN_MS, $party->resumed_at_ms); // play time is in the future
        $this->assertLessThan(0, $party->positionMs());
        $this->assertSame(0, $party->positionSeconds()); // reactions during the countdown count as 0:00
    }

    public function test_reactions_are_pinned_to_the_shared_clock_and_feed_anonymous_moments(): void
    {
        $ana = $this->user('ana');
        $party = $this->hostParty($ana);
        $this->playingAt($party, 3725); // 1:02:05

        $first = $this->actingAs($ana)->postJson(route('watch-along.react', $party->code), ['kind' => 'scream'])
            ->assertOk()->assertJsonPath('emoji', '😱')->json();
        $this->assertEqualsWithDelta(3725, $first['pos'], 2);

        $this->postJson(route('watch-along.react', $party->code), ['kind' => 'text', 'body' => 'no way'])->assertOk()->assertJsonPath('body', 'no way');
        $this->postJson(route('watch-along.react', $party->code), ['kind' => 'text'])->assertUnprocessable();
        $this->postJson(route('watch-along.react', $party->code), ['kind' => 'dance'])->assertUnprocessable();

        // Polling with ?after= only returns newer reactions.
        $this->getJson(route('watch-along.state', ['code' => $party->code, 'after' => $first['id']]))
            ->assertJsonCount(1, 'reactions')->assertJsonPath('reactions.0.body', 'no way');

        // Only emoji reactions count toward the title's anonymous moments (minute 62).
        $this->assertSame(1, TitleMoment::where(['tmdb_id' => 550, 'minute' => 62, 'kind' => 'scream'])->value('count'));
        $this->assertSame(1, TitleMoment::count());
    }

    public function test_anyone_can_pause_and_resume_and_the_host_can_shift(): void
    {
        $ana = $this->user('ana');
        $ben = $this->user('ben');
        $party = $this->hostParty($ana);
        $this->actingAs($ben)->post(route('watch-along.join', $party->code));
        $this->playingAt($party, 600);

        $this->actingAs($ben)->postJson(route('watch-along.control', $party->code), ['action' => 'pause'])
            ->assertJsonPath('status', 'paused')->assertJsonPath('lastEvent', 'Ben paused at 10:00');
        $party->refresh();
        $this->assertEqualsWithDelta(600_000, $party->base_ms, 2000);
        usleep(50_000);
        $this->assertSame($party->base_ms, $party->positionMs()); // the clock stands still while paused

        $this->postJson(route('watch-along.control', $party->code), ['action' => 'shift', 'seconds' => 30])->assertForbidden();
        $this->actingAs($ana)->postJson(route('watch-along.control', $party->code), ['action' => 'shift', 'seconds' => -3600]);
        $this->assertSame(0, $party->refresh()->base_ms); // never before 0:00

        $this->actingAs($ben)->postJson(route('watch-along.control', $party->code), ['action' => 'resume'])->assertJsonPath('status', 'playing');
        $this->assertGreaterThan(WatchParty::nowMs(), $party->refresh()->resumed_at_ms); // 3-second "press play" countdown
    }

    public function test_ending_shows_the_moment_map_and_posts_to_friends_feeds(): void
    {
        [$ana, $ben] = [$this->user('ana'), $this->user('ben')];
        $party = $this->hostParty($ana);
        $this->actingAs($ben)->post(route('watch-along.join', $party->code));
        auth()->logout();
        $this->post(route('watch-along.join', $party->code), ['name' => 'Guest Kim']);

        $this->playingAt($party, 3600);
        foreach (['scream', 'scream', 'laugh'] as $kind) {
            $this->actingAs($ben)->postJson(route('watch-along.react', $party->code), ['kind' => $kind]);
        }

        $this->postJson(route('watch-along.control', $party->code), ['action' => 'end'])->assertForbidden();
        $this->actingAs($ana)->postJson(route('watch-along.control', $party->code), ['action' => 'end'])->assertJsonPath('status', 'ended');

        $this->actingAs($ben)->get(route('watch-along.show', $party->code))->assertOk()
            ->assertSee('3 people watched together')->assertSee('Your moment map')->assertSee('Biggest moment');

        // Friends feed: one post per member with an account (not the guest), with the reaction counts.
        $posts = Activity::where('type', 'watched_along')->get();
        $this->assertEqualsCanonicalizing([$ana->id, $ben->id], $posts->pluck('user_id')->all());
        $this->assertSame(['scream' => 2, 'laugh' => 1], $posts->first()->data['reactions']);
        $this->assertSame(1, $posts->first()->data['guests']);
        $this->assertSame('friends', $posts->first()->visibility);

        // Joining an ended party isn't possible; outsiders don't see names.
        $this->flushSession();
        $this->actingAs($this->user('cara'))->get(route('watch-along.show', $party->code))->assertSee('This Watch Along has ended')->assertDontSee('Guest Kim');
    }

    public function test_short_watch_alongs_do_not_post_and_long_ones_end_by_themselves(): void
    {
        $ana = $this->user('ana');
        $party = $this->hostParty($ana);
        $this->playingAt($party, 60);
        $this->actingAs($ana)->postJson(route('watch-along.control', $party->code), ['action' => 'end']);
        $this->assertSame(0, Activity::where('type', 'watched_along')->count());

        $late = $this->hostParty($ana);
        $this->playingAt($late, $late->runtime_seconds + WatchParty::OVERRUN_SECONDS + 5);
        $this->getJson(route('watch-along.state', $late->code))->assertJsonPath('status', 'ended');
    }

    public function test_invites_go_to_friends_only_once(): void
    {
        Notification::fake();
        [$ana, $ben, $cara] = [$this->user('ana'), $this->user('ben'), $this->user('cara')];
        foreach ([[$ana, $ben], [$ben, $ana], [$ana, $cara]] as [$a, $b]) {
            Follow::create(['follower_id' => $a->id, 'followee_id' => $b->id, 'status' => 'accepted']);
        }
        $party = $this->hostParty($ana);

        $this->get(route('watch-along.show', $party->code))->assertSee('Invite friends')->assertSee('Ben Test')->assertDontSee('Cara Test');
        $this->postJson(route('watch-along.invite', $party->code), ['user_id' => $cara->id])->assertForbidden(); // one-way follow
        $this->postJson(route('watch-along.invite', $party->code), ['user_id' => $ben->id])->assertOk();
        $this->postJson(route('watch-along.invite', $party->code), ['user_id' => $ben->id])->assertOk();

        Notification::assertSentToTimes($ben, WatchAlongInvite::class, 1);
    }

    public function test_a_leftover_session_token_is_not_honoured_for_another_account(): void
    {
        $party = $this->hostParty($this->user('ana'));
        // Same browser session, someone else signs in.
        $this->actingAs($this->user('ben'))->getJson(route('watch-along.state', $party->code))->assertForbidden();
    }

    public function test_title_pages_show_envydeo_moments_once_there_is_enough_data(): void
    {
        foreach (range(1, 9) as $i) {
            TitleMoment::bump(550, 'movie', 64, 'scream');
        }
        Livewire::withoutLazyLoading()->test(TitleSocial::class, ['tmdbId' => 550, 'type' => 'movie', 'title' => 'Fight Club'])->assertDontSee('Envydeo moments');

        TitleMoment::bump(550, 'movie', 12, 'laugh');
        Livewire::withoutLazyLoading()->test(TitleSocial::class, ['tmdbId' => 550, 'type' => 'movie', 'title' => 'Fight Club'])
            ->assertSee('Envydeo moments')->assertSee('Jumpiest moment')->assertSee('1:04:00');
    }

    public function test_expired_parties_are_pruned(): void
    {
        $party = $this->hostParty($this->user('ana'));
        $party->update(['expires_at' => now()->subMinute()]);

        $this->artisan('model:prune', ['--model' => [WatchParty::class]]);
        $this->assertSame(0, WatchParty::count());
    }
}
