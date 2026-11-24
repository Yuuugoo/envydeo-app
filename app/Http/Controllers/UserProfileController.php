<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\User;
use App\Models\Watchlist;
use App\Services\SocialGraph;
use App\Services\Tmdb;
use Illuminate\Http\Request;

/** Public profile pages at /@username, with privacy applied by SocialGraph. */
class UserProfileController extends Controller
{
    public function show(Request $request, string $username, SocialGraph $graph, Tmdb $tmdb)
    {
        $owner = $this->findVisible($request, $username, $graph);
        $viewer = $request->user();
        $canView = $graph->canViewProfile($viewer, $owner);
        $isFriend = $graph->canViewFriendsContent($viewer, $owner);

        $diary = collect();
        $watchlist = collect();
        $checkIns = collect();

        if ($canView) {
            $diary = $owner->diaryEntries()->withCount('likes')->latest('watched_on')->latest()->limit(24)->get();

            $items = Watchlist::where('user_id', $owner->id)->latest()->limit(18)->get(['tmdb_id', 'media_type']);
            $details = $tmdb->many($items->mapWithKeys(fn ($w) => [$w->media_type . '-' . $w->tmdb_id => [$w->tmdbPath()]])->all(), Tmdb::DAY);
            $watchlist = $items
                ->map(fn ($w) => ($d = $details[$w->media_type . '-' . $w->tmdb_id] ?? []) && !empty($d['id']) ? $d + ['media_type' => $w->media_type] : null)
                ->filter()->values();
        }
        if ($isFriend) {
            $checkIns = CheckIn::where('user_id', $owner->id)->latest()->limit(18)->get();
        }

        return view('social.profile', [
            'owner' => $owner,
            'relation' => $graph->relation($viewer, $owner),
            'canView' => $canView,
            'isFriend' => $isFriend,
            'diary' => $diary,
            'watchlist' => $watchlist,
            'checkIns' => $checkIns,
            'stats' => [
                'followers' => count($graph->followerIds($owner)),
                'following' => count($graph->followingIds($owner)),
                'watched' => $owner->diaryEntries()->count(),
                'loved' => $owner->diaryEntries()->where('verdict', 'loved')->count(),
                'matches' => $owner->activities()->where('type', 'matched')->count(),
            ],
        ]);
    }

    public function connections(Request $request, string $username, string $list, SocialGraph $graph)
    {
        $owner = $this->findVisible($request, $username, $graph);
        abort_unless($graph->canViewProfile($request->user(), $owner), 403);

        $hidden = $request->user() ? $graph->hiddenIds($request->user()) : [];
        $ids = $list === 'followers' ? $graph->followerIds($owner) : $graph->followingIds($owner);
        $people = User::whereIn('id', array_diff($ids, $hidden))->whereNotNull('username')->orderBy('name')->paginate(30);

        return view('social.connections', ['owner' => $owner, 'list' => $list, 'people' => $people]);
    }

    /** 404 for unknown users AND for blocked pairs, so blocking doesn't reveal the account. */
    private function findVisible(Request $request, string $username, SocialGraph $graph): User
    {
        $owner = User::where('username', strtolower($username))->firstOrFail();
        abort_if($graph->isBlockedBetween($request->user(), $owner), 404);

        return $owner;
    }
}
