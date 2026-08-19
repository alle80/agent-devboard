<?php

namespace Alle80\Devboard\Http\Middleware;

use Alle80\Devboard\Admin;
use Closure;
use Illuminate\Http\Request;

/** Admin-only pages of the board (settings, context): 403 for everybody else. See Alle80\Devboard\Admin. */
class DevboardAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Admin::allows($request->user()), 403, 'Administrators only.');

        return $next($request);
    }
}
