<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Settings\AppSettings;
use Carbon\Carbon;

/**
 * Lingua dell'interfaccia della board. La sceglie l'impostazione `app.locale` di /settings
 * ('' = come il config `app.locale` dell'applicazione, cioè APP_LOCALE) e la applica il
 * middleware SetLocale a ogni richiesta della board (comprese quelle di Livewire).
 * Le lingue disponibili sono le cartelle di traduzione del package (e quelle pubblicate).
 */
class Locale
{
    /** Nomi mostrati nel selettore; per una lingua non elencata si usa intl o il codice. */
    public const NAMES = [
        'en' => 'English',
        'it' => 'Italiano',
    ];

    /** Codici delle lingue in cui la board è tradotta (cartelle in resources/lang + lang/vendor/griglia). */
    public static function available(): array
    {
        $dirs = [__DIR__.'/../../resources/lang'];
        if (function_exists('lang_path')) {
            $dirs[] = lang_path('vendor/griglia');
        }

        $codes = [];
        foreach ($dirs as $dir) {
            foreach ((array) glob(rtrim($dir, '/').'/*', GLOB_ONLYDIR) as $path) {
                $codes[basename($path)] = true;
            }
        }
        $codes = array_keys($codes);
        sort($codes);

        return $codes;
    }

    /** Nome della lingua nella lingua stessa («Italiano», «English»). */
    public static function name(string $code): string
    {
        if (isset(self::NAMES[$code])) {
            return self::NAMES[$code];
        }
        if (class_exists(\Locale::class)) {
            $label = \Locale::getDisplayLanguage($code, $code);
            if ($label !== '' && $label !== $code) {
                return ucfirst($label);
            }
        }

        return strtoupper($code);
    }

    /** Opzioni della select in /settings: '' = come nel config, poi una voce per lingua. */
    public static function options(): array
    {
        $options = ['' => __('griglia::t.settings_options.locale_app', ['locale' => strtoupper((string) config('app.locale', 'en'))])];
        foreach (self::available() as $code) {
            $options[$code] = self::name($code);
        }

        return $options;
    }

    /** Lingua scelta in /settings, o '' se non ne è stata scelta una (o non è più disponibile). */
    public static function chosen(): string
    {
        $chosen = '';
        try {
            $chosen = (string) app(AppSettings::class)->locale;
        } catch (\Throwable) {
            // impostazioni non ancora migrate
        }

        return in_array($chosen, self::available(), true) ? $chosen : '';
    }

    /** Lingua con cui la board si mostra adesso: quella scelta, altrimenti quella dell'applicazione. */
    public static function current(): string
    {
        return self::chosen() ?: app()->getLocale();
    }

    /**
     * Applica la lingua scelta all'applicazione (e a Carbon, per le date «3 ore fa»).
     * Senza una scelta non tocca nulla: la board resta nella lingua dell'applicazione ospite.
     */
    public static function apply(): void
    {
        $locale = self::chosen();
        if ($locale === '') {
            return;
        }
        app()->setLocale($locale);
        Carbon::setLocale($locale);
    }
}
