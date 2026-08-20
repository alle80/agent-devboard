<?php

namespace Alle80\Griglia\Http\Controllers;

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
            'endpoint' => ['required', 'string', 'max:500', 'url', 'starts_with:https://', function (string $attr, mixed $value, \Closure $fail) {
                if (! self::endpointAllowed((string) $value)) {
                    $fail('push endpoint host not allowed');
                }
            }],
            'keys.p256dh' => ['nullable', 'string'],
            'keys.auth' => ['nullable', 'string'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);
        $user->updatePushSubscription($data['endpoint'], $data['keys']['p256dh'] ?? null, $data['keys']['auth'] ?? null, $data['contentEncoding'] ?? null);

        return response()->json(['ok' => true]);
    }

    /** Only https endpoints of known push services (config griglia.push_allowed_hosts; empty = any https host). */
    public static function endpointAllowed(string $endpoint): bool
    {
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));
        if ($host === '' || strtolower((string) parse_url($endpoint, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }
        $allowed = (array) config('griglia.push_allowed_hosts', []);
        if ($allowed === []) {
            return true;
        }
        foreach ($allowed as $pattern) {
            if (fnmatch(strtolower($pattern), $host)) {
                return true;
            }
        }

        return false;
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
            $user->notify(new \Alle80\Griglia\Notifications\TestNotification);
        }

        return response()->json(['ok' => true]);
    }
}
