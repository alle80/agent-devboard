<?php

namespace Alle80\Griglia\Http\Middleware;

use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Themes;
use Closure;
use Illuminate\Http\Request;

/**
 * Ricorda in sessione lo stile della lista che si sta guardando (manga, jack, c64, slate…),
 * così le pagine "senza stile proprio" (es. /settings) si vestono allo stesso modo.
 */
class RememberStyle
{
    public function handle(Request $request, Closure $next)
    {
        $prefix = trim((string) config('griglia.route_prefix', ''), '/');
        $slug = trim($request->path(), '/');
        if ($prefix !== '' && str_starts_with($slug, $prefix)) {
            $slug = trim(substr($slug, strlen($prefix)), '/');
        }
        if ($slug === '') {
            $configured = app(AppSettings::class)->default_style;
            session(['style' => Themes::has($configured) ? $configured : Themes::default()]);
        }

        return $next($request);
    }

    /** Current style: session, then the default style from /settings, then the default theme. */
    public static function current(): string
    {
        $style = session('style') ?: (app(AppSettings::class)->default_style ?: Themes::default());

        return Themes::known($style) ? $style : Themes::default();
    }
}
