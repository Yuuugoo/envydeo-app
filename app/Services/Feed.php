<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\CheckIn;
use App\Models\User;
use Illuminate\Support\Collection;

/** Builds the friends feed and the "Tonight" strip, applying SocialGraph's visibility rules. */
class Feed
{
    public function __construct(private SocialGraph $graph) {}

    /**
     * Activities from people the viewer follows (and the viewer), newest first.
     * "friends" items (check-ins) only from mutual friends. Blocked people never appear.
     */
    public function activities(User $viewer, int $limit = 20, ?int $beforeId = null): Collection
    {
        $hidden = $this->graph->hiddenIds($viewer);
        $actors = array_values(array_diff([...$this->graph->followingIds($viewer), $viewer->id], $hidden));
        $friendsOrMe = [...$this->graph->friendIds($viewer), $viewer->id];

        return Activity::with(['user', 'subject'])
            ->whereIn('user_id', $actors)
            ->where(fn ($q) => $q->where('visibility', 'public')->orWhereIn('user_id', $friendsOrMe))
            ->when($beforeId, fn ($q) => $q->where('id', '<', $beforeId))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Live check-ins (last 24h) from mutual friends and the viewer, one group per person:
     * the viewer first, then friends with the most recent check-in first.
     *
     * @return Collection<int, array{user: User, checkIns: Collection}>
     */
    public function tonight(User $viewer): Collection
    {
        $ids = array_values(array_diff([...$this->graph->friendIds($viewer), $viewer->id], $this->graph->hiddenIds($viewer)));

        return CheckIn::with(['user', 'reactions.user'])
            ->whereIn('user_id', $ids)
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->groupBy('user_id')
            ->map(fn ($checkIns) => ['user' => $checkIns->first()->user, 'checkIns' => $checkIns->sortBy('created_at')->values()])
            ->sortBy(fn ($group) => $group['user']->is($viewer) ? 0 : 1)
            ->values();
    }

    /** People the viewer might want to follow: friends of friends, then active public profiles. */
    public function suggestions(User $viewer, int $limit = 5): Collection
    {
        $exclude = [...$this->graph->followingIds($viewer), ...$this->graph->hiddenIds($viewer), $viewer->id];
        $friendsOfFriends = \App\Models\Follow::whereIn('follower_id', $this->graph->friendIds($viewer))
            ->where('status', 'accepted')
            ->whereNotIn('followee_id', $exclude)
            ->selectRaw('followee_id, count(*) as mutuals')
            ->groupBy('followee_id')
            ->orderByDesc('mutuals')
            ->limit($limit)
            ->pluck('mutuals', 'followee_id');

        $users = User::whereIn('id', $friendsOfFriends->keys())->whereNotNull('username')->get()
            ->map(fn ($u) => $u->setAttribute('mutuals', $friendsOfFriends[$u->id]));

        if ($users->count() < $limit) {
            $users = $users->merge(
                User::whereNotNull('username')->where('is_private', false)
                    ->whereNotIn('id', [...$exclude, ...$users->pluck('id')])
                    ->withCount('diaryEntries')->orderByDesc('diary_entries_count')->latest()
                    ->limit($limit - $users->count())->get()
            );
        }

        return $users->values();
    }
}
