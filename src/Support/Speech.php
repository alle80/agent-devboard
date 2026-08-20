<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Settings\AppSettings;

/**
 * Speech to text mode: 'browser' = Web Speech API in the browser (free, quality varies, phones restart
 * the session at every pause); 'server' = the audio is recorded in the browser and transcribed by the
 * Laravel AI SDK (`Laravel\Ai\Transcription`, provider `ai.default_for_transcription`, e.g. OpenAI);
 * 'auto' (default) = server when available, else browser.
 */
class Speech
{
    /** Effective mode for the front-end: 'server' or 'browser'. */
    public static function mode(): string
    {
        $pref = 'auto';
        try {
            $pref = (string) app(AppSettings::class)->speech_mode;
        } catch (\Throwable) {
            // settings not migrated yet
        }
        if ($pref === 'browser') {
            return 'browser';
        }
        if ($pref === 'server') {
            return self::serverAvailable() ? 'server' : 'browser';
        }

        return self::serverAvailable() ? 'server' : 'browser';
    }

    /** True when the AI SDK is installed and a transcription provider with a key is configured. */
    public static function serverAvailable(): bool
    {
        if (! class_exists(\Laravel\Ai\Transcription::class)) {
            return false;
        }
        $provider = (string) config('ai.default_for_transcription', '');
        if ($provider === '') {
            return false;
        }
        $providers = array_map('trim', explode(',', $provider));
        foreach ($providers as $p) {
            if (! empty(config("ai.providers.$p.key"))) {
                return true;
            }
        }

        return false;
    }

    /** Two-letter language for the transcription (from the app locale). */
    public static function language(): string
    {
        return substr(str_replace('_', '-', (string) app()->getLocale()), 0, 2) ?: 'en';
    }
}
