<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Saves/removes the browser's push subscription so notifications can reach this device. */
class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aes128gcm',
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => ['required', 'string', 'max:500']]);
        $request->user()->deletePushSubscription($request->input('endpoint'));

        return response()->json(['ok' => true]);
    }
}
