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

    /**
     * Everything the browser needs to dictate (window.GRIGLIA_SPEECH): mode, endpoint and the labels the
     * mic button shows on its own (errors, silence warning, retry) — the JS has no access to the
     * translations, so a missing label here means a mute failure for the user.
     */
    public static function frontend(): array
    {
        return [
            'mode' => self::mode(),
            'url' => route('griglia.transcribe'),
            'csrf' => csrf_token(),
            'lang' => self::language(),
            'max_seconds' => self::maxSeconds(),
            'start' => __('griglia::t.mic_start'),
            'stop' => __('griglia::t.mic_stop'),
            'busy' => __('griglia::t.mic_busy'),
            'error' => __('griglia::t.mic_error'),
            'retry' => __('griglia::t.mic_retry'),
            'empty' => __('griglia::t.mic_empty'),
            'silent' => __('griglia::t.mic_silent'),
            'denied' => __('griglia::t.mic_denied'),
            'lost' => __('griglia::t.mic_lost'),
            'expired' => __('griglia::t.mic_expired'),
            'kept' => __('griglia::t.mic_kept'),
            'recovered' => __('griglia::t.mic_recovered'),
            'limit' => __('griglia::t.mic_limit'),
        ];
    }

    /**
     * Hard limit of a single dictation, in seconds (0 = none): when it is reached the recording is closed
     * and transcribed instead of growing until the upload or the provider refuses it.
     */
    public static function maxSeconds(): int
    {
        return max(0, (int) config('griglia.speech_max_seconds', 300));
    }

    /** Two-letter language for the transcription (from the app locale). */
    public static function language(): string
    {
        return substr(str_replace('_', '-', (string) app()->getLocale()), 0, 2) ?: 'en';
    }

    /**
     * Clean audio mime type for the transcription provider.
     *
     * Browsers send the codec as a parameter (`audio/webm;codecs=opus`) and the provider picks the
     * decoder from the mime/extension: with the parameter attached the file is sent as `audio.mp3`
     * and the API answers «Audio file might be corrupted or unsupported». Chrome/Firefox dictation
     * used to fail entirely because of this.
     */
    public static function audioMime(?string $raw, ?string $filename = null): string
    {
        $mime = strtolower(trim(explode(';', (string) $raw)[0]));

        if ($mime === 'video/webm') {
            $mime = 'audio/webm';   // audio-only webm is often guessed as video/webm
        }
        if ($mime === 'video/mp4') {
            $mime = 'audio/mp4';
        }

        $known = ['audio/webm', 'audio/ogg', 'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'audio/wav',
            'audio/x-wav', 'audio/mpeg', 'audio/mp3', 'audio/mpga', 'audio/flac', 'audio/x-flac'];

        if (in_array($mime, $known, true)) {
            return $mime;
        }

        return match (strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION))) {
            'webm' => 'audio/webm',
            'ogg', 'oga', 'opus' => 'audio/ogg',
            'mp4', 'm4a' => 'audio/mp4',
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'flac' => 'audio/flac',
            default => 'audio/webm',
        };
    }

    /**
     * Vocabulary hint sent with the audio: without it the provider writes what it hears in plain
     * language («con la gente» instead of «con l'agente»). Config `griglia.speech_prompt` wins;
     * '' disables the hint.
     */
    public static function prompt(): string
    {
        $configured = config('griglia.speech_prompt');

        if (is_string($configured)) {
            return trim($configured);
        }

        return trim((string) __('griglia::t.speech_prompt'));
    }
}
