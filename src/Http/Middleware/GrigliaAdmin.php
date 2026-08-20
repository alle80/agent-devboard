<?php

namespace Alle80\Griglia\Http\Middleware;

use Alle80\Griglia\Admin;
use Closure;
use Illuminate\Http\Request;

/** Admin-only pages of the board (settings, context): 403 for everybody else. See Alle80\Griglia\Admin. */
class GrigliaAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Admin::allows($request->user()), 403, 'Administrators only.');

        return $next($request);
    }
}
