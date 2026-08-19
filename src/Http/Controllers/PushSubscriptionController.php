<?php

namespace Alle80\Devboard\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Web Push: the browser registers/removes its subscription for the logged-in user (HasPushSubscriptions). */
class PushSubscriptionController
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'updatePushSubscription')) {
            return response()->json(['ok' => false, 'error' => 'push not available'], 422);
        }
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['nullable', 'string'],
            'keys.auth' => ['nullable', 'string'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);
        $user->updatePushSubscription($data['endpoint'], $data['keys']['p256dh'] ?? null, $data['keys']['auth'] ?? null, $data['contentEncoding'] ?? null);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $endpoint = (string) $request->input('endpoint', '');
        if ($user && $endpoint !== '' && method_exists($user, 'deletePushSubscription')) {
            $user->deletePushSubscription($endpoint);
        }

        return response()->json(['ok' => true]);
    }

    /** The user asks for a test notification on their devices (settings page). */
    public function test(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && method_exists($user, 'notify')) {
            $user->notify(new \Alle80\Devboard\Notifications\TestNotification);
        }

        return response()->json(['ok' => true]);
    }
}
