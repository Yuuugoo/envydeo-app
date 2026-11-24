<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Block;
use App\Models\Follow;
use App\Models\User;
use App\Notifications\FollowAccepted;
use App\Notifications\FollowRequested;
use App\Notifications\NewFollower;
use Illuminate\Support\Facades\DB;

/**
 * The single place that decides who can see what.
 *
 *  - Following: anyone can follow a public account; private accounts approve requests (status "pending").
 *  - Friends: two people who follow each other (both accepted). "Tonight" check-ins are friends-only.
 *  - Profile content (diary, reviews, watchlist, Top 4): public accounts → everyone; private → accepted followers.
 *  - Blocking hides both people from each other everywhere and removes follows in both directions.
 *
 * Results are memoised per request (this service is a scoped singleton).
 */
class SocialGraph
{
    private array $memo = [];

    // ---------------------------------------------------------------- reads

    /** @return list<string> ids this user follows (accepted) */
    public function followingIds(User $user): array
    {
        return $this->memo["following:{$user->id}"] ??= Follow::where('follower_id', $user->id)
            ->where('status', 'accepted')->pluck('followee_id')->all();
    }

    /** @return list<string> ids following this user (accepted) */
    public function followerIds(User $user): array
    {
        return $this->memo["followers:{$user->id}"] ??= Follow::where('followee_id', $user->id)
            ->where('status', 'accepted')->pluck('follower_id')->all();
    }

    /** @return list<string> mutual follows */
    public function friendIds(User $user): array
    {
        return $this->memo["friends:{$user->id}"] ??= array_values(array_intersect($this->followingIds($user), $this->followerIds($user)));
    }

    /** @return list<string> everyone hidden from this user (blocked by them, or who blocked them) */
    public function hiddenIds(User $user): array
    {
        return $this->memo["hidden:{$user->id}"] ??= Block::where('blocker_id', $user->id)->pluck('blocked_id')
            ->merge(Block::where('blocked_id', $user->id)->pluck('blocker_id'))
            ->unique()->values()->all();
    }

    public function isBlockedBetween(?User $a, ?User $b): bool
    {
        return $a && $b && in_array($b->id, $this->hiddenIds($a), true);
    }

    public function follows(?User $viewer, User $owner): bool
    {
        return $viewer && in_array($owner->id, $this->followingIds($viewer), true);
    }

    public function areFriends(?User $a, ?User $b): bool
    {
        return $a && $b && in_array($b->id, $this->friendIds($a), true);
    }

    /** Diary, reviews, watchlist, Top 4, followers lists. */
    public function canViewProfile(?User $viewer, User $owner): bool
    {
        if ($viewer?->is($owner)) {
            return true;
        }
        if ($this->isBlockedBetween($viewer, $owner)) {
            return false;
        }

        return !$owner->is_private || $this->follows($viewer, $owner);
    }

    /** "Tonight" check-ins (and their photos). */
    public function canViewFriendsContent(?User $viewer, User $owner): bool
    {
        if ($viewer?->is($owner)) {
            return true;
        }

        return !$this->isBlockedBetween($viewer, $owner) && $this->areFriends($viewer, $owner);
    }

    /** Relationship summary for buttons on profiles and people lists. */
    public function relation(?User $viewer, User $owner): array
    {
        $outgoing = $viewer ? Follow::where('follower_id', $viewer->id)->where('followee_id', $owner->id)->first() : null;
        $incoming = $viewer ? Follow::where('follower_id', $owner->id)->where('followee_id', $viewer->id)->first() : null;

        return [
            'self' => (bool) $viewer?->is($owner),
            'following' => $outgoing?->status === 'accepted',
            'requested' => $outgoing?->status === 'pending',
            'follows_you' => $incoming?->status === 'accepted',
            'requested_you' => $incoming?->status === 'pending',
            'friends' => $outgoing?->status === 'accepted' && $incoming?->status === 'accepted',
            'blocked' => $viewer ? Block::where('blocker_id', $viewer->id)->where('blocked_id', $owner->id)->exists() : false,
            'hidden' => $this->isBlockedBetween($viewer, $owner),
        ];
    }

    // ---------------------------------------------------------------- writes

    /** Follow (or request to follow a private account). Returns the resulting status, or null if not allowed. */
    public function follow(User $viewer, User $owner): ?string
    {
        if ($viewer->is($owner) || $this->isBlockedBetween($viewer, $owner)) {
            return null;
        }

        $existing = Follow::where('follower_id', $viewer->id)->where('followee_id', $owner->id)->first();
        if ($existing) {
            return $existing->status;
        }

        $status = $owner->is_private ? 'pending' : 'accepted';
        Follow::create(['follower_id' => $viewer->id, 'followee_id' => $owner->id, 'status' => $status]);
        $this->forget();

        if ($status === 'pending') {
            $owner->notify(new FollowRequested($viewer));
        } else {
            $owner->notify(new NewFollower($viewer));
            Activity::record($viewer, 'followed', null, ['user_id' => $owner->id, 'username' => $owner->username, 'name' => $owner->name]);
        }

        return $status;
    }

    public function unfollow(User $viewer, User $owner): void
    {
        Follow::where('follower_id', $viewer->id)->where('followee_id', $owner->id)->delete();
        $this->forget();
    }

    public function accept(User $owner, User $requester): bool
    {
        $updated = Follow::where('follower_id', $requester->id)->where('followee_id', $owner->id)
            ->where('status', 'pending')->update(['status' => 'accepted']);
        $this->forget();

        if ($updated) {
            $requester->notify(new FollowAccepted($owner));
            Activity::record($requester, 'followed', null, ['user_id' => $owner->id, 'username' => $owner->username, 'name' => $owner->name]);
        }

        return (bool) $updated;
    }

    public function decline(User $owner, User $requester): void
    {
        Follow::where('follower_id', $requester->id)->where('followee_id', $owner->id)->where('status', 'pending')->delete();
        $this->forget();
    }

    public function removeFollower(User $owner, User $follower): void
    {
        Follow::where('follower_id', $follower->id)->where('followee_id', $owner->id)->delete();
        $this->forget();
    }

    public function block(User $viewer, User $target): void
    {
        if ($viewer->is($target)) {
            return;
        }

        DB::transaction(function () use ($viewer, $target) {
            Block::firstOrCreate(['blocker_id' => $viewer->id, 'blocked_id' => $target->id]);
            Follow::where(fn ($q) => $q->where('follower_id', $viewer->id)->where('followee_id', $target->id))
                ->orWhere(fn ($q) => $q->where('follower_id', $target->id)->where('followee_id', $viewer->id))
                ->delete();
        });
        $this->forget();
    }

    public function unblock(User $viewer, User $target): void
    {
        Block::where('blocker_id', $viewer->id)->where('blocked_id', $target->id)->delete();
        $this->forget();
    }

    private function forget(): void
    {
        $this->memo = [];
    }
}
