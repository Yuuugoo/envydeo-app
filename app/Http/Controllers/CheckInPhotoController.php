<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Services\SocialGraph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Check-in photos are friends-only: every request is authorised; files never sit on the public disk. */
class CheckInPhotoController extends Controller
{
    public function __invoke(Request $request, CheckIn $checkIn, SocialGraph $graph)
    {
        $allowed = $graph->canViewFriendsContent($request->user(), $checkIn->user)
            || ($request->user()?->can('moderate') ?? false); // moderators reviewing a report
        abort_unless($checkIn->photo_path && $allowed, 404);

        $disk = Storage::disk(CheckIn::PHOTO_DISK);
        abort_unless($disk->exists($checkIn->photo_path), 404);

        return response()->file($disk->path($checkIn->photo_path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400', // never cached by shared proxies
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
