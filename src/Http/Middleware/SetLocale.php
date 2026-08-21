<?php

namespace Alle80\Griglia\Http\Middleware;

use Alle80\Griglia\Support\Locale;
use Closure;
use Illuminate\Http\Request;

/**
 * Veste ogni pagina della board con la lingua scelta in /settings (impostazione `app.locale`).
 * È anche middleware «persistente» di Livewire, così le richieste /livewire/update
 * (modali, salvataggi) restituiscono le stesse stringhe della pagina.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        Locale::apply();

        return $next($request);
    }
}
