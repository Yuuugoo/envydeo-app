<?php

namespace Tests\Feature;

use App\Livewire\MovieNight\Room;
use App\Livewire\Social\CheckInCard;
use App\Livewire\Social\CheckInComposer;
use App\Livewire\Social\LogTitle;
use App\Livewire\Social\Onboarding;
use App\Livewire\Social\ReportModal;
use App\Livewire\Social\ReportsAdmin;
use App\Livewire\Social\ReviewCard;
use App\Models\Activity;
use App\Models\CheckIn;
use App\Models\DiaryEntry;
use App\Models\Follow;
use App\Models\MatchRoom;
use App\Models\Report;
use App\Models\User;
use App\Models\Watchlist;
use App\Notifications\CheckInReacted;
use App\Notifications\FollowAccepted;
use App\Notifications\FollowRequested;
use App\Notifications\FriendCheckedIn;
use App\Notifications\MovieNightInvite;
use App\Notifications\NewFollower;
use App\Notifications\ReviewLiked;
use App\Services\Feed;
use App\Services\SocialGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SocialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $movie = fn (int $id) => ['id' => $id, 'title' => "Movie {$id}", 'poster_path' => "/p{$id}.jpg", 'backdrop_path' => "/b{$id}.jpg", 'release_date' => '2021-05-01', 'vote_average' => 7.1, 'genre_ids' => [28]];
        Http::fake([
            'api.themoviedb.org/3/search/multi*' => Http::response(['results' => [$movie(550) + ['media_type' => 'movie', 'popularity' => 10]]]),
            'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => [$movie(1), $movie(2), $movie(3)]]),
            'api.themoviedb.org/3/movie/*' => fn ($r) => Http::response($movie((int) last(explode('/', parse_url($r->url(), PHP_URL_PATH))))),
            'api.themoviedb.org/*' => Http::response([]),
        ]);
    }

    private function user(string $username, bool $private = false): User
    {
        return User::factory()->create(['name' => ucfirst($username), 'username' => $username, 'is_private' => $private]);
    }

    private function follow(User $a, User $b, string $status = 'accepted'): void
    {
        Follow::create(['follower_id' => $a->id, 'followee_id' => $b->id, 'status' => $status]);
    }

    private function friends(User $a, User $b): void
    {
        $this->follow($a, $b);
        $this->follow($b, $a);
    }

    private function checkIn(User $owner, bool $photo = false): CheckIn
    {
        $path = null;
        if ($photo) {
            $path = "check-ins/{$owner->id}/test.jpg";
            Storage::disk('local')->put($path, 'jpeg-bytes');
        }

        return CheckIn::create(['user_id' => $owner->id, 'tmdb_id' => 550, 'media_type' => 'movie', 'movie' => ['id' => 550, 'type' => 'movie', 'title' => 'Fight Club', 'poster_path' => '/p.jpg', 'year' => '1999'], 'photo_path' => $path, 'expires_at' => now()->addDay()]);
    }

    // ------------------------------------------------------------------ onboarding

    public function test_social_pages_require_a_username_first(): void
    {
        $user = User::factory()->create(['username' => null]);
        $this->actingAs($user)->get('/feed')->assertRedirect(route('welcome', ['next' => url('/feed')]));
    }

    public function test_onboarding_validates_usernames_and_blocks_open_redirects(): void
    {
        $this->user('taken');
        $user = User::factory()->create(['name' => 'Ana Reyes', 'username' => null]);

        $component = Livewire::actingAs($user)->test(Onboarding::class)->assertSet('username', 'anareyes');
        foreach (['taken', 'admin', 'Bad Name!', 'ab'] as $bad) {
            $component->set('username', $bad)->call('save')->assertHasErrors('username');
        }

        $component->set('next', 'https://evil.example.com/phish')->set('username', 'ana_r')->call('save')->assertRedirect(route('feed'));
        $this->assertSame('ana_r', $user->fresh()->username);
    }

    // ------------------------------------------------------------------ following & privacy

    public function test_following_public_and_private_accounts(): void
    {
        Notification::fake();
        $graph = app(SocialGraph::class);
        [$ana, $pub, $priv] = [$this->user('ana'), $this->user('pub'), $this->user('priv', private: true)];

        $this->assertSame('accepted', $graph->follow($ana, $pub));
        Notification::assertSentTo($pub, NewFollower::class);

        $this->assertSame('pending', $graph->follow($ana, $priv));
        Notification::assertSentTo($priv, FollowRequested::class);
        $this->assertFalse($graph->canViewProfile($ana, $priv));

        $graph->accept($priv, $ana);
        Notification::assertSentTo($ana, FollowAccepted::class);
        $this->assertTrue(app(SocialGraph::class)->canViewProfile($ana, $priv));
    }

    public function test_private_profiles_hide_content_and_blocking_hides_everything(): void
    {
        [$ana, $priv] = [$this->user('ana'), $this->user('priv', private: true)];
        DiaryEntry::create(['user_id' => $priv->id, 'tmdb_id' => 550, 'movie' => ['id' => 550, 'title' => 'Secret Pick', 'poster_path' => '/p.jpg'], 'rating' => 8, 'watched_on' => now()]);

        $this->actingAs($ana)->get('/@priv')->assertOk()->assertSee('This account is private')->assertDontSee('Secret Pick');

        $this->follow($ana, $priv);
        $this->app->forgetScopedInstances(); // a fresh request (SocialGraph memoises per request)
        $this->actingAs($ana)->get('/@priv')->assertOk()->assertSee('Secret Pick');

        app(SocialGraph::class)->block($priv, $ana);
        $this->app->forgetScopedInstances();
        $this->assertSame(0, Follow::count(), 'blocking removes follows both ways');
        $this->actingAs($ana)->get('/@priv')->assertNotFound();
        $this->actingAs($priv)->get('/@ana')->assertNotFound();
    }

    // ------------------------------------------------------------------ check-ins

    public function test_check_ins_are_friends_only_and_notify_friends(): void
    {
        Notification::fake();
        [$ana, $friend, $fan, $muted] = [$this->user('ana'), $this->user('friend'), $this->user('fan'), $this->user('muted')];
        $this->friends($ana, $friend);
        $this->follow($fan, $ana); // one-way follower: not a friend
        $this->friends($ana, $muted);
        $muted->update(['notify_friend_checkins' => false]);

        Livewire::actingAs($ana)->test(CheckInComposer::class)
            ->call('openSheet', 550, 'movie')
            ->set('caption', 'Popcorn ready')
            ->set('photo', UploadedFile::fake()->image('couch.jpg', 2400, 1600))
            ->call('share')
            ->assertHasNoErrors();

        $checkIn = CheckIn::firstOrFail();
        Notification::assertSentTo($friend, FriendCheckedIn::class);
        Notification::assertNotSentTo($fan, FriendCheckedIn::class);
        Notification::assertNotSentTo($muted, FriendCheckedIn::class);

        // Photo: re-encoded JPEG, downsized, private disk; served only to friends.
        $stored = Storage::disk('local')->get($checkIn->photo_path);
        [$w, $h, $type] = getimagesizefromstring($stored);
        $this->assertSame([1440, 960, IMAGETYPE_JPEG], [$w, $h, $type]);
        $this->actingAs($friend)->get(route('check-ins.photo', $checkIn))->assertOk();
        $this->actingAs($fan)->get(route('check-ins.photo', $checkIn))->assertNotFound();

        // Feed: friend sees the check-in; the one-way follower does not.
        $this->assertTrue(app(Feed::class)->activities($friend)->contains('subject_id', $checkIn->id));
        $this->assertFalse(app(Feed::class)->activities($fan)->contains('type', 'checked_in'));
        $this->assertCount(1, app(Feed::class)->tonight($friend));
    }

    public function test_reactions_and_im_in(): void
    {
        Notification::fake();
        [$ana, $ben, $stranger] = [$this->user('ana'), $this->user('ben'), $this->user('stranger')];
        $this->friends($ana, $ben);
        $checkIn = $this->checkIn($ana);

        Livewire::actingAs($ben)->test(CheckInCard::class, ['checkInId' => $checkIn->id])->call('react', 'in');
        $this->assertTrue(Watchlist::where('user_id', $ben->id)->where('tmdb_id', 550)->exists());
        Notification::assertSentTo($ana, CheckInReacted::class);

        Livewire::actingAs($ana)->test(CheckInCard::class, ['checkInId' => $checkIn->id])->call('react', 'love')->assertForbidden();
        Livewire::actingAs($stranger)->test(CheckInCard::class, ['checkInId' => $checkIn->id])->assertForbidden();
    }

    // ------------------------------------------------------------------ diary & reviews

    public function test_logging_a_title_and_liking_reviews(): void
    {
        Notification::fake();
        [$ana, $ben] = [$this->user('ana'), $this->user('ben')];
        $this->follow($ben, $ana);

        Livewire::actingAs($ana)->test(LogTitle::class, ['tmdbId' => 550, 'type' => 'movie'])
            ->call('start')->call('save')->assertHasErrors('rating') // needs a rating or words
            ->set('rating', 9)->set('review', 'Rules 1 and 2.')->set('hasSpoilers', true)->call('save')->assertHasNoErrors();

        $entry = DiaryEntry::firstOrFail();
        $this->assertSame([9, 4.5, true], [$entry->rating, $entry->stars(), $entry->has_spoilers]);
        $this->assertTrue(app(Feed::class)->activities($ben)->contains('subject_id', $entry->id));

        Livewire::actingAs($ben)->test(ReviewCard::class, ['entryId' => $entry->id])->call('toggleLike');
        Notification::assertSentTo($ana, ReviewLiked::class);
        Livewire::actingAs($ana)->test(ReviewCard::class, ['entryId' => $entry->id])->call('toggleLike')->assertForbidden();
    }

    // ------------------------------------------------------------------ notifications, push, invites

    public function test_push_subscriptions_are_saved_and_removed(): void
    {
        $ana = $this->user('ana');
        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123';

        $this->actingAs($ana)->postJson('/push-subscriptions', ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'key', 'auth' => 'auth']])->assertOk();
        $this->assertSame(1, $ana->pushSubscriptions()->count());
        $this->actingAs($ana)->postJson('/push-subscriptions', ['endpoint' => 'http://insecure.test', 'keys' => ['p256dh' => 'k', 'auth' => 'a']])->assertUnprocessable();

        $this->actingAs($ana)->deleteJson('/push-subscriptions', ['endpoint' => $endpoint])->assertOk();
        $this->assertSame(0, $ana->pushSubscriptions()->count());
    }

    public function test_only_mutual_friends_can_be_invited_to_a_movie_night(): void
    {
        Notification::fake();
        [$ana, $friend, $fan] = [$this->user('ana'), $this->user('friend'), $this->user('fan')];
        $this->friends($ana, $friend);
        $this->follow($fan, $ana);

        $this->actingAs($ana)->post('/movie-night/quick');
        $room = MatchRoom::firstOrFail();

        $component = Livewire::actingAs($ana)->test(Room::class, ['room' => $room])->assertSee('Invite friends')->assertSee('Friend');
        $component->call('inviteFriend', $friend->id)->call('inviteFriend', $fan->id)->call('inviteFriend', $friend->id);

        Notification::assertSentToTimes($friend, MovieNightInvite::class, 1); // throttled
        Notification::assertNotSentTo($fan, MovieNightInvite::class);
    }

    // ------------------------------------------------------------------ safety

    public function test_reports_and_moderation(): void
    {
        config(['envydeo.admin_emails' => ['mod@example.com']]);
        [$ana, $ben, $stranger] = [$this->user('ana'), $this->user('ben'), $this->user('stranger')];
        $mod = User::factory()->create(['email' => 'mod@example.com', 'username' => 'mod']);
        $this->friends($ana, $ben);
        $checkIn = $this->checkIn($ana, photo: true);

        Livewire::actingAs($stranger)->test(ReportModal::class)->call('openSheet', 'check_in', $checkIn->id)->call('submit')->assertForbidden();
        Livewire::actingAs($ben)->test(ReportModal::class)->call('openSheet', 'check_in', $checkIn->id)->set('reason', 'inappropriate')->call('submit')->assertSet('sent', true);
        $this->assertSame(1, Report::count());

        $this->actingAs($ben)->get('/admin/reports')->assertForbidden();
        $this->actingAs($mod)->get(route('check-ins.photo', $checkIn))->assertOk(); // moderators can review the photo

        Livewire::actingAs($mod)->test(ReportsAdmin::class)->call('removeContent', Report::first()->id);
        $this->assertNull(CheckIn::find($checkIn->id));
        Storage::disk('local')->assertMissing($checkIn->photo_path);
        $this->assertNotNull(Report::first()->resolved_at);
    }

    public function test_deleting_an_account_removes_photos_and_social_data(): void
    {
        [$ana, $ben] = [$this->user('ana'), $this->user('ben')];
        $this->friends($ana, $ben);
        $checkIn = $this->checkIn($ana, photo: true);
        Activity::record($ana, 'checked_in', $checkIn, [], 'friends');

        $ana->delete();

        Storage::disk('local')->assertMissing($checkIn->photo_path);
        $this->assertSame(0, CheckIn::count());
        $this->assertSame(0, Follow::count());
        $this->assertSame(0, Activity::count());
    }
}
