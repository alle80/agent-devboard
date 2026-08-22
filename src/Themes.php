<?php

namespace Alle80\Griglia;

/**
 * Registry of the visual styles.
 *
 * Two kinds of entries:
 *  - generic themes: rendered by ThemedTodoList with the shared views and CSS variables (.theme-<slug>);
 *    the package ships "slate"; more can be added via config('griglia.themes') or Themes::registerTheme().
 *  - dedicated styles: routes with their own Livewire components/views (registered by the host app
 *    with Themes::registerStyle(), e.g. the manga/jacovitti/c64 versions of the original app).
 *
 * The switcher menu lists both. Settings skins (how /settings dresses per style) come from
 * settingsSkin(): generic themes get an automatic skin, dedicated styles register theirs.
 */
class Themes
{
    /** Extra generic themes registered at runtime (slug => definition). */
    protected static array $themes = [];

    /** Dedicated styles (slug => [label, icon, icon_img?, route]). */
    protected static array $styles = [];

    /** Settings-page skins for dedicated styles (slug => skin). */
    protected static array $skins = [];

    /**
     * Text fields of a theme definition: the words the board prints (claim and footer of the page, counter
     * «3/5 done», done_all, the add button, the stamp of completed tasks, the delete confirm, the placeholder
     * of the insert form and of the sub-tasks). Localized by get() — see text().
     */
    public const TEXT_KEYS = ['claim', 'counter', 'done_all', 'add', 'stamp', 'footer', 'confirm', 'placeholder'];

    /** Built-in generic theme shipped with the package: its texts are translation keys, so they follow the board language. */
    public static function builtin(): array
    {
        return [
            'slate' => [
                'label' => 'Slate',
                'icon' => '🪨',
                'icon_img' => '/vendor/griglia/images/slate/slate.svg',
                'fonts' => 'jetbrains-mono:400,700',
                'claim' => '',
                'counter' => 'griglia::t.theme.counter',
                'done_all' => 'griglia::t.theme.done_all',
                'add' => 'griglia::t.theme.add',
                'stamp' => 'griglia::t.theme.stamp',
                'footer' => '',
                'confirm' => 'griglia::t.theme.confirm',
                'placeholder' => 'griglia::t.theme.placeholder',
                'deco' => [],
            ],
        ];
    }

    public static function registerTheme(string $slug, array $definition): void
    {
        static::$themes[$slug] = $definition;
    }

    public static function registerStyle(string $slug, array $definition): void
    {
        static::$styles[$slug] = $definition;
    }

    public static function registerSkin(string $slug, array $skin): void
    {
        static::$skins[$slug] = $skin;
    }

    /**
     * All generic themes: built-in + config('griglia.themes') + runtime registrations + installed packs,
     * as defined (texts not localized: see get()). A config or runtime entry whose slug is a built-in theme
     * overrides it key by key (e.g. only `icon_img`), so the rest — the translated texts included — stays;
     * other slugs replace each other entirely.
     */
    public static function all(): array
    {
        $builtin = static::builtin();
        $all = $builtin;
        foreach ([(array) config('griglia.themes', []), static::$themes] as $layer) {
            foreach ($layer as $slug => $definition) {
                $all[$slug] = isset($builtin[$slug]) ? array_merge($all[$slug], (array) $definition) : (array) $definition;
            }
        }

        // Installed packs (storage) override themes registered in code, never the built-in ones
        return array_merge($all, array_diff_key(ThemeStore::installed(), $builtin));
    }

    public static function has(string $slug): bool
    {
        return isset(static::all()[$slug]);
    }

    /** Definition of a theme as the views use it: texts resolved for the current locale. */
    public static function get(string $slug): array
    {
        return static::localize(static::all()[$slug]);
    }

    /** The definition with every text field (TEXT_KEYS) resolved for the current locale. */
    public static function localize(array $definition): array
    {
        foreach (self::TEXT_KEYS as $key) {
            if (array_key_exists($key, $definition)) {
                $definition[$key] = static::text($definition[$key]);
            }
        }

        return $definition;
    }

    /**
     * Text of a theme field in a locale (the current one by default). A definition may give it as:
     *  - a translation key — `griglia::t.theme.add`, the built-in way: the board language applies;
     *  - a literal — used as it is (a JSON translation of the host app, if any, applies);
     *  - a per-locale map — `['en' => 'add', 'it' => 'aggiungi']`: the locale asked for, then
     *    `app.fallback_locale`, then the first entry.
     */
    public static function text(mixed $value, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        if (is_array($value)) {
            $value = $value[$locale] ?? $value[(string) config('app.fallback_locale', 'en')] ?? (reset($value) ?: '');

            return is_string($value) ? $value : '';
        }
        $value = is_scalar($value) ? (string) $value : '';

        return $value === '' ? '' : (string) __($value, [], $locale);
    }

    public static function default(): string
    {
        $slug = (string) config('griglia.default_theme', 'slate');

        return static::has($slug) ? $slug : array_key_first(static::all());
    }

    /** Dedicated styles registered by the host app. */
    public static function styles(): array
    {
        return static::$styles;
    }

    /**
     * Switcher entries: slug => [label, icon, icon_img?, url]. Dedicated styles first (as registered),
     * then generic themes.
     */
    public static function switcher(): array
    {
        $all = [];
        foreach (static::$styles as $slug => $s) {
            $all[$slug] = ['label' => $s['label'], 'icon' => $s['icon'] ?? '', 'icon_img' => $s['icon_img'] ?? null, 'url' => $s['route']];
        }
        foreach (static::all() as $slug => $t) {
            $all[$slug] = ['label' => $t['label'], 'icon' => $t['icon'] ?? '', 'icon_img' => $t['icon_img'] ?? null, 'url' => static::url($slug)];
        }

        return $all;
    }

    /** URL of the board (generic themes no longer have a slug-specific page). */
    public static function url(string $slug): string
    {
        return '/'.trim((string) config('griglia.route_prefix', ''), '/');
    }

    /** Is this slug a known style (generic theme or dedicated)? */
    public static function known(string $slug): bool
    {
        return isset(static::switcher()[$slug]);
    }

    /**
     * Skin of the /settings page for a style: layout + classes + CSS variables of the switches.
     * Generic themes share one skin driven by their .theme-<slug> variables.
     */
    public static function settingsSkin(string $style): array
    {
        if (isset(static::$skins[$style])) {
            return static::$skins[$style];
        }

        if (! static::has($style)) {
            $style = static::default();
        }

        return [
            'layout' => 'griglia::layouts.themed', 'layoutData' => ['theme' => $style], 'home' => static::url($style),
            'card' => 'tl-card p-5',
            'h1' => 'tl-display tl-title text-2xl',
            'h2' => 'tl-display tl-accent text-xl',
            'sub' => 'text-sm italic opacity-70', 'label' => 'font-bold', 'help' => 'text-sm opacity-70',
            'input' => 'tl-input px-3 py-1.5 focus:outline-none',
            'back' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1.5',
            'divide' => 'divide-y divide-current/15',
            'vars' => '--set-on:var(--tl-accent,#16a34a);--set-off:var(--tl-input,var(--tl-bg,#fff));--set-border:var(--tl-bcol,currentColor);--set-knob:var(--tl-accent-fg,#fff);--set-shadow:none',
        ];
    }
}
