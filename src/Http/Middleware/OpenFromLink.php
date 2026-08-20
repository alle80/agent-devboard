<?php

namespace Alle80\Griglia\Http\Middleware;

use Alle80\Griglia\Models\Checklist;
use Closure;
use Illuminate\Http\Request;

/**
 * Deep links from notifications: `?list=ID` switches to that list (if it is the user's) and `?open=ID`
 * asks the board to open that todo's modal after the page renders (session flag read by the list view).
 */
class OpenFromLink
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && (auth()->check() || \Alle80\Griglia\Mode::isLocal())) {
            if (($list = (int) $request->query('list')) && Checklist::mine()->whereKey($list)->exists()) {
                session(['checklist_id' => $list]);
            }
            if ($open = (int) $request->query('open')) {
                session(['griglia_open_todo' => $open]);
            }
        }

        return $next($request);
    }
}
