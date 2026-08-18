<?php

namespace Alle80\Devboard\Http\Middleware;

use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Themes;
use Closure;
use Illuminate\Http\Request;

/**
 * Su «/»: se in /settings è scelto uno stile predefinito diverso da manga, si va dritti lì.
 * `?stay=1` (o `?style=manga`) evita il redirect, così la versione manga resta raggiungibile.
 */
class RedirectToDefaultStyle
{
    public function handle(Request $request, Closure $next)
    {
        $style = app(AppSettings::class)->default_style;

        if ($style !== '' && ! $request->boolean('stay') && Themes::known($style)) {
            return redirect(Themes::switcher()[$style]['url']);
        }

        return $next($request);
    }
}
