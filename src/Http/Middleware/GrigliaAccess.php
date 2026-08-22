<?php

namespace Alle80\Griglia\Http\Middleware;

use Alle80\Griglia\Mode;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Guards the board routes according to the mode:
 *  - local  → everybody in (no authentication);
 *  - server → an authenticated user is required (same behaviour as the `auth` middleware: redirect to
 *             login) and, on top of it, the user must pass the access check: `canAccessGriglia(): bool`
 *             on the user model if defined (Filament/Nova style), else the Gate ability named by config
 *             `griglia.access_gate` if set, else any authenticated user.
 */
class GrigliaAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Mode::isLocal()) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            $login = ! $request->expectsJson() && \Illuminate\Support\Facades\Route::has('login') ? route('login', absolute: false) : null;
            throw new AuthenticationException('Unauthenticated.', ['web'], $login);
        }

        if (method_exists($user, 'canAccessGriglia')) {
            abort_unless((bool) $user->canAccessGriglia(), 403, __('griglia::t.errors.forbidden'));
        } elseif ($gate = config('griglia.access_gate')) {
            abort_unless(Gate::forUser($user)->allows($gate), 403, __('griglia::t.errors.forbidden'));
        }

        return $next($request);
    }
}
