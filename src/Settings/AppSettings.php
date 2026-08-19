<?php

namespace Alle80\Devboard\Settings;

use Alle80\Devboard\Themes;
use Spatie\LaravelSettings\Settings;

/**
 * Impostazioni dell'app (comportamento della board), gruppo «app». Vedi AgentSettings per l'assistente.
 */
class AppSettings extends Settings
{
    /** Stile aperto da «/»: '' = manga (nessun redirect), altrimenti slug (jack, c64, slate…). */
    public string $default_style;

    /** Lunghezza massima del titolo di un todo. */
    public int $title_max_length;

    /** Archivia da solo i completati più vecchi di N giorni (0 = mai). */
    public int $auto_archive_days;

    /** Descrizione AI delle immagini caricate (per la ricerca). */
    public bool $ai_describe_images;

    /** Provider AI per le immagini: '' = da .env (AI_IMAGE_PROVIDERS/AI_PROVIDER), altrimenti nome provider. */
    public string $ai_image_provider;

    /** Modello AI per le immagini: '' = da .env / il più economico del provider. */
    public string $ai_image_model;

    /** Toast in pagina quando lo stato viene cambiato da console (es. Claude prende in carico). */
    public bool $toast_console_changes;

    /** Lato del pannello a scomparsa della dashboard su desktop: 'right' | 'left'. */
    public string $tab_side;

    /** Board mode override: '' = follow config devboard.mode, 'local' = no authentication (global lists), 'server' = authenticated users. */
    public string $mode;

    /** Show the slide-out DASHBOARD tab (desktop). */
    public bool $show_dashboard_tab;

    /** Speech to text: 'auto' (server if configured, else browser), 'server' (AI SDK transcription), 'browser' (Web Speech API). */
    public string $speech_mode;

    /** Board notifications (task closed / question asked) in the in-app bell 🔔. */
    public bool $notify_in_app;

    /** Board notifications as Web Push on the devices that enabled them. */
    public bool $notify_webpush;

    /** Board notifications by e-mail (needs a configured mailer). */
    public bool $notify_mail;

    public static function group(): string
    {
        return 'app';
    }

    public static function fields(): array
    {
        $styles = ['' => __('devboard::t.settings_options.default_style_none')];
        foreach (Themes::switcher() as $slug => $s) {
            $styles[$slug] = ($s['icon'] ?? '').' '.$s['label'];
        }

        $providers = ['' => __('devboard::t.settings_options.ai_provider_env')];
        foreach (array_keys((array) config('ai.providers', [])) as $name) {
            $providers[$name] = $name;
        }

        $labels = (array) __('devboard::t.settings_fields');
        $def = [
            'default_style' => ['select', $styles],
            'title_max_length' => ['int', ['min' => 10, 'max' => 200]],
            'auto_archive_days' => ['int', ['min' => 0, 'max' => 365]],
            'ai_describe_images' => ['bool', []],
            'ai_image_provider' => ['select', $providers],
            'ai_image_model' => ['text', []],
            'toast_console_changes' => ['bool', []],
            'mode' => ['select', [
                '' => __('devboard::t.settings_options.mode_config'),
                'server' => __('devboard::t.settings_options.mode_server'),
                'local' => __('devboard::t.settings_options.mode_local'),
            ]],
            'show_dashboard_tab' => ['bool', []],
            'speech_mode' => ['select', [
                'auto' => __('devboard::t.settings_options.speech_auto'),
                'server' => __('devboard::t.settings_options.speech_server'),
                'browser' => __('devboard::t.settings_options.speech_browser'),
            ]],
            'notify_in_app' => ['bool', []],
            'notify_webpush' => ['bool', []],
            'notify_mail' => ['bool', []],
            'tab_side' => ['select', [
                'right' => __('devboard::t.settings_options.tab_side_right'),
                'left' => __('devboard::t.settings_options.tab_side_left'),
            ]],
        ];
        $out = [];
        foreach ($def as $key => [$type, $opts]) {
            [$label, $help] = $labels[$key] ?? [$key, ''];
            $out[$key] = [$label, $help, $type, $opts];
        }

        return $out;
    }
}
