<?php

namespace Alle80\Devboard;

/**
 * Registry of the visual styles.
 *
 * Two kinds of entries:
 *  - generic themes: rendered by ThemedTodoList with the shared views and CSS variables (.theme-<slug>);
 *    the package ships "slate"; more can be added via config('devboard.themes') or Themes::registerTheme().
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

    /** Built-in generic theme shipped with the package. */
    public static function builtin(): array
    {
        return [
            'slate' => [
                'label' => 'Slate',
                'icon' => '🪨',
                'fonts' => 'jetbrains-mono:400,700',
                'claim' => 'todo',
                'counter' => 'done',
                'done_all' => 'all done',
                'add' => 'add a task',
                'stamp' => 'done',
                'footer' => '',
                'confirm' => 'delete «:title»?',
                'placeholder' => 'write here…',
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

    /** All generic themes: built-in + config('devboard.themes') + runtime registrations. */
    public static function all(): array
    {
        // Installed packs (storage) override themes registered in code, never the built-in ones
        return array_merge(static::builtin(), (array) config('devboard.themes', []), static::$themes, array_diff_key(ThemeStore::installed(), static::builtin()));
    }

    public static function has(string $slug): bool
    {
        return isset(static::all()[$slug]);
    }

    public static function get(string $slug): array
    {
        return static::all()[$slug];
    }

    public static function default(): string
    {
        $slug = (string) config('devboard.default_theme', 'slate');

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

    /** URL of a generic theme page (honours the route prefix). */
    public static function url(string $slug): string
    {
        return '/'.trim(config('devboard.route_prefix', '').'/'.$slug, '/');
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
            'layout' => 'devboard::layouts.themed', 'layoutData' => ['theme' => $style], 'home' => static::url($style),
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
