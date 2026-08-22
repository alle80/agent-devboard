<?php

namespace Alle80\Griglia\Settings;

use Alle80\Griglia\Mode;
use Alle80\Griglia\Support\Locale;
use Alle80\Griglia\Themes;
use Spatie\LaravelSettings\Settings;

/**
 * Impostazioni dell'app (comportamento della board), gruppo «app». Vedi AgentSettings per l'assistente.
 */
class AppSettings extends Settings
{
    /** Lingua dell'interfaccia: '' = come il config dell'applicazione (APP_LOCALE), altrimenti 'en', 'it'… */
    public string $locale;

    /** Tema mostrato su «/»: vuoto o slug non valido = tema di fallback configurato. */
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

    /** Board mode override: '' = follow config griglia.mode, 'local' = no authentication (global lists), 'server' = authenticated users. */
    public string $mode;

    /** Show the slide-out DASHBOARD tab (desktop). */
    public bool $show_dashboard_tab;

    /** Speech to text: 'auto' (server if configured, else browser), 'server' (AI SDK transcription), 'browser' (Web Speech API). */
    public string $speech_mode;

    /** Price per million input tokens (statistics: cost = tokens × price); 0 = unknown. */
    public string $cost_per_m_in;

    /** Price per million output tokens; 0 = unknown. */
    public string $cost_per_m_out;

    /** Currency symbol/code shown with costs. */
    public string $cost_currency;

    /** Agent context: when on, the host sync writes the enabled blocks to the instruction files; when off, the originals are restored. */
    public bool $context_sync;

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
        $styles = ['' => __('griglia::t.settings_options.default_style_none')];
        foreach (Themes::all() as $slug => $s) {
            $styles[$slug] = ($s['icon'] ?? '').' '.$s['label'];
        }

        $providers = ['' => __('griglia::t.settings_options.ai_provider_env')];
        foreach (array_keys((array) config('ai.providers', [])) as $name) {
            $providers[$name] = $name;
        }

        $labels = (array) __('griglia::t.settings_fields');
        $def = [
            'locale' => ['select', Locale::options()],
            'default_style' => ['select', $styles],
            'title_max_length' => ['int', ['min' => 10, 'max' => 200]],
            'auto_archive_days' => ['int', ['min' => 0, 'max' => 365]],
            'ai_describe_images' => ['bool', []],
            'ai_image_provider' => ['select', $providers],
            'ai_image_model' => ['text', []],
            'toast_console_changes' => ['bool', []],
            'mode' => ['select', array_filter([
                '' => __('griglia::t.settings_options.mode_config'),
                'server' => __('griglia::t.settings_options.mode_server'),
                'local' => Mode::localFromUiAllowed() ? __('griglia::t.settings_options.mode_local') : null,
            ])],
            'show_dashboard_tab' => ['bool', []],
            'speech_mode' => ['select', [
                'auto' => __('griglia::t.settings_options.speech_auto'),
                'server' => __('griglia::t.settings_options.speech_server'),
                'browser' => __('griglia::t.settings_options.speech_browser'),
            ]],
            'cost_per_m_in' => ['text', []],
            'cost_per_m_out' => ['text', []],
            'cost_currency' => ['text', []],
            'context_sync' => ['bool', []],
            'notify_in_app' => ['bool', []],
            'notify_webpush' => ['bool', []],
            'notify_mail' => ['bool', []],
            'tab_side' => ['select', [
                'right' => __('griglia::t.settings_options.tab_side_right'),
                'left' => __('griglia::t.settings_options.tab_side_left'),
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
