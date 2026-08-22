<?php

namespace Alle80\Griglia;

use Alle80\Griglia\Support\Locale;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Installable theme packs. A pack is a zip with:
 *   theme.json  — the theme definition (slug, label, icon, fonts, texts, deco, optional icon_img, version, author)
 *   theme.css   — CSS variables of the theme (.theme-<slug> { --tl-… }) and any rule scoped to .theme-<slug>
 *   images/…    — optional images (icon_img and anything referenced by the CSS with relative urls)
 * Packs live in storage/app/themes/<slug>/ and are served by the griglia.theme-asset route
 * (/griglia-themes/{slug}/{path}). Installed themes are merged into Themes::all() at runtime and
 * override themes registered in code with the same slug (never the built-in ones nor dedicated styles).
 */
class ThemeStore
{
    /** Keys allowed in theme.json (anything else is dropped). */
    public const KEYS = ['slug', 'label', 'icon', 'icon_img', 'fonts', 'claim', 'counter', 'done_all', 'add', 'stamp', 'footer', 'confirm', 'placeholder', 'deco', 'version', 'author', 'description'];

    /** File extensions accepted inside a pack. */
    public const EXTENSIONS = ['json', 'css', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'woff', 'woff2', 'ttf', 'otf', 'md', 'txt']; // no svg: scriptable

    /** Limits of a pack: per file, whole pack, number of entries. */
    public const MAX_FILE_BYTES = 5 * 1024 * 1024;

    public const MAX_TOTAL_BYTES = 20 * 1024 * 1024;

    public const MAX_ENTRIES = 200;

    protected static ?array $cache = null;

    /** Forget the installed-pack snapshot after external storage cleanup (notably isolated test runs). */
    public static function clearCache(): void
    {
        static::$cache = null;
    }

    /**
     * Theme CSS is linked on every page of the theme: strip what can call home or run code —
     * `@import` of anything, `url()` / `src()` / `image-set()` pointing outside the pack (http(s)://, //, data:
     * except images), `expression()`, `-moz-binding`, `behavior:`. Relative URLs (images/, fonts/) are kept.
     */
    public static function sanitizeCss(string $css): string
    {
        $css = preg_replace('/@import\b[^;]*;?/i', '/* import removed */', $css);
        $css = preg_replace('/expression\s*\(/i', '/* removed */(', $css);
        $css = preg_replace('/-moz-binding\s*:[^;}]*/i', '/* removed */', $css);
        $css = preg_replace('/\bbehavior\s*:[^;}]*/i', '/* removed */', $css);
        $css = preg_replace_callback('/\b(url|src|image-set)\s*\(\s*([\'"]?)([^\'")]*)\2\s*\)/i', function ($m) {
            $target = trim($m[3]);
            if (preg_match('#^(https?:)?//#i', $target) || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target) && ! preg_match('#^data:image/(png|jpe?g|gif|webp);#i', $target)) {
                return $m[1].'("")'; // external / non-image scheme → neutralised
            }
            if (str_contains($target, '..')) {
                return $m[1].'("")';
            }

            return $m[0];
        }, $css);

        return $css;
    }

    public static function root(): string
    {
        return storage_path('app/themes');
    }

    public static function path(string $slug, string $file = ''): string
    {
        return rtrim(static::root().'/'.$slug.'/'.$file, '/');
    }

    /** Public URL of a file of an installed theme. */
    public static function url(string $slug, string $file): string
    {
        return url('/griglia-themes/'.$slug.'/'.ltrim($file, '/'));
    }

    public static function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9-]{1,40}$/', $slug);
    }

    /** Installed themes: slug => definition (with resolved css_url / icon_img). */
    public static function installed(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $out = [];
        foreach (File::glob(static::root().'/*/theme.json') as $json) {
            $slug = basename(dirname($json));
            $def = json_decode((string) File::get($json), true);
            if (! is_array($def) || ! static::isValidSlug($slug)) {
                continue;
            }
            $out[$slug] = static::normalize($slug, $def);
        }

        return static::$cache = $out;
    }

    /** Definition as used by the views (fills defaults, resolves URLs). */
    protected static function normalize(string $slug, array $def): array
    {
        $def = array_intersect_key($def, array_flip(self::KEYS));
        $def['slug'] = $slug;
        $def += [
            'label' => Str::headline($slug), 'icon' => '🎨', 'fonts' => '', 'claim' => '', 'footer' => '', 'deco' => [],
            // texts the pack leaves out: the translated ones of the built-in theme (board language)
            'counter' => 'griglia::t.theme.counter', 'done_all' => 'griglia::t.theme.done_all', 'add' => 'griglia::t.theme.add',
            'stamp' => 'griglia::t.theme.stamp', 'confirm' => 'griglia::t.theme.confirm', 'placeholder' => 'griglia::t.theme.placeholder',
        ];
        foreach (Themes::TEXT_KEYS as $key) {
            // a translation key, a literal, or a per-locale map {"en": "…", "it": "…"} (see Themes::text()); anything else is dropped
            $def[$key] = is_array($def[$key])
                ? array_filter($def[$key], fn ($text, $locale) => is_string($text) && is_string($locale), ARRAY_FILTER_USE_BOTH)
                : (is_scalar($def[$key]) ? (string) $def[$key] : '');
        }
        $def['deco'] = array_values(array_filter((array) $def['deco'], 'is_string'));
        if (! empty($def['icon_img'])) {
            // only files inside the pack (no external URLs: tracking / mixed content)
            $def['icon_img'] = preg_match('#^(https?:)?//#', $def['icon_img']) || str_contains($def['icon_img'], '..') ? null : static::url($slug, ltrim($def['icon_img'], '/'));
        }
        if (is_file(static::path($slug, 'theme.css'))) {
            $def['css_url'] = static::url($slug, 'theme.css').'?v='.filemtime(static::path($slug, 'theme.css'));
        }
        $def['installed'] = true;

        return $def;
    }

    /**
     * Install a pack from a zip file. Returns the definition. Throws RuntimeException with a message
     * meant for the user when the pack is invalid.
     */
    public static function install(string $zipPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException(__('griglia::t.themes.err_zip'));
        }

        // Find theme.json (allow one top-level folder inside the zip)
        $prefix = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^(?:([^/]+)/)?theme\.json$#', $name, $m)) {
                $prefix = isset($m[1]) && $m[1] !== '' ? $m[1].'/' : '';
                break;
            }
        }
        if ($prefix === null) {
            $zip->close();
            throw new RuntimeException(__('griglia::t.themes.err_missing_json'));
        }

        $def = json_decode((string) $zip->getFromName($prefix.'theme.json'), true);
        $slug = is_array($def) ? (string) ($def['slug'] ?? '') : '';
        if (! is_array($def) || ! static::isValidSlug($slug)) {
            $zip->close();
            throw new RuntimeException(__('griglia::t.themes.err_invalid_json'));
        }
        if (isset(Themes::builtin()[$slug]) || isset(Themes::styles()[$slug]) || in_array($slug, ['settings', 'login', 'logout', 'register'], true)) {
            $zip->close();
            throw new RuntimeException(__('griglia::t.themes.err_reserved', ['slug' => $slug]));
        }

        $target = static::path($slug);
        $tmp = static::root().'/.tmp-'.Str::random(8);
        File::ensureDirectoryExists($tmp);

        try {
            if ($zip->numFiles > self::MAX_ENTRIES) {
                throw new RuntimeException(__('griglia::t.themes.err_too_many_files', ['max' => self::MAX_ENTRIES]));
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($prefix !== '' && ! str_starts_with($name, $prefix)) {
                    continue;
                }
                $rel = substr($name, strlen($prefix));
                if ($rel === '' || str_ends_with($rel, '/') || str_starts_with($rel, '__MACOSX') || str_contains($rel, '..') || str_starts_with($rel, '/')) {
                    continue;
                }
                $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
                if (! in_array($ext, self::EXTENSIONS, true)) {
                    continue;
                }
                // Declared (uncompressed) size BEFORE reading: zip bombs are refused without inflating them
                $stat = $zip->statIndex($i);
                $declared = (int) ($stat['size'] ?? 0);
                if ($declared > self::MAX_FILE_BYTES) {
                    continue;
                }
                if ($total + $declared > self::MAX_TOTAL_BYTES) {
                    throw new RuntimeException(__('griglia::t.themes.err_too_big_total', ['max' => (int) (self::MAX_TOTAL_BYTES / 1024 / 1024)]));
                }
                $data = $zip->getFromIndex($i);
                if ($data === false || strlen($data) > self::MAX_FILE_BYTES) {
                    continue;
                }
                $total += strlen($data);
                if ($total > self::MAX_TOTAL_BYTES) {
                    throw new RuntimeException(__('griglia::t.themes.err_too_big_total', ['max' => (int) (self::MAX_TOTAL_BYTES / 1024 / 1024)]));
                }
                if ($ext === 'css') {
                    $data = self::sanitizeCss($data);
                }
                File::ensureDirectoryExists(dirname($tmp.'/'.$rel));
                File::put($tmp.'/'.$rel, $data);
            }
            $zip->close();

            if (! is_file($tmp.'/theme.json')) {
                throw new RuntimeException(__('griglia::t.themes.err_missing_json'));
            }

            // Only the known keys are kept
            File::put($tmp.'/theme.json', json_encode(array_intersect_key($def, array_flip(self::KEYS)) + ['slug' => $slug], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            File::deleteDirectory($target);
            File::ensureDirectoryExists(dirname($target));
            File::moveDirectory($tmp, $target);
        } finally {
            File::deleteDirectory($tmp);
        }

        static::$cache = null;

        return static::installed()[$slug];
    }

    public static function uninstall(string $slug): bool
    {
        if (! static::isValidSlug($slug) || ! is_dir(static::path($slug))) {
            return false;
        }
        File::deleteDirectory(static::path($slug));
        static::$cache = null;

        return true;
    }

    /**
     * Build a zip pack from any known generic theme (installed, config, registered or built-in).
     * For themes defined in code, the CSS is extracted from $cssFrom (all top-level rules whose
     * selector mentions .theme-<slug>) when given.
     */
    public static function export(string $slug, string $outFile, ?string $cssFrom = null): string
    {
        $themes = Themes::all();
        if (! isset($themes[$slug])) {
            throw new RuntimeException("Unknown theme: {$slug}");
        }
        $def = array_intersect_key($themes[$slug], array_flip(self::KEYS));
        $def['slug'] = $slug;

        $zip = new ZipArchive;
        File::ensureDirectoryExists(dirname($outFile));
        if ($zip->open($outFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot write {$outFile}");
        }

        if (isset(static::installed()[$slug])) {
            // Installed: copy the pack as it is
            $base = static::path($slug);
            foreach (File::allFiles($base) as $f) {
                $zip->addFile($f->getPathname(), $f->getRelativePathname());
            }
        } else {
            // Texts given as translation keys (the built-in theme) are written as per-locale maps: the
            // exported theme.json is readable and stays multilingual
            foreach (Themes::TEXT_KEYS as $key) {
                $def[$key] = static::exportText($def[$key] ?? '');
            }
            if (! empty($def['icon_img']) && str_starts_with($def['icon_img'], '/') && is_file(public_path($def['icon_img']))) {
                $zip->addFile(public_path($def['icon_img']), 'images/'.basename($def['icon_img']));
                $def['icon_img'] = 'images/'.basename($def['icon_img']);
            }
            $zip->addFromString('theme.json', json_encode($def, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $css = $cssFrom && is_file($cssFrom) ? static::extractCss((string) File::get($cssFrom), $slug) : '';
            $zip->addFromString('theme.css', $css !== '' ? $css : "/* CSS variables of the theme */\n.theme-{$slug} {\n}\n");
        }
        $zip->close();

        return $outFile;
    }

    /** A translation key becomes {locale: text} for every language of the board; literals and maps stay as they are. */
    protected static function exportText(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || ! Lang::has($value)) {
            return $value;
        }
        $texts = [];
        foreach (Locale::available() as $locale) {
            $texts[$locale] = Themes::text($value, $locale);
        }

        return $texts;
    }

    /** All top-level CSS rules whose selector mentions .theme-<slug> (no nesting/@media support needed here). */
    public static function extractCss(string $css, string $slug): string
    {
        $out = [];
        $len = strlen($css);
        $i = 0;
        while ($i < $len) {
            $open = strpos($css, '{', $i);
            if ($open === false) {
                break;
            }
            $selector = trim(substr($css, $i, $open - $i));
            // skip comments before the selector
            $selector = trim((string) preg_replace('#/\*.*?\*/#s', '', $selector));
            $depth = 1;
            $j = $open + 1;
            while ($j < $len && $depth > 0) {
                if ($css[$j] === '{') {
                    $depth++;
                } elseif ($css[$j] === '}') {
                    $depth--;
                }
                $j++;
            }
            $block = substr($css, $open, $j - $open);
            if ($selector !== '' && $selector[0] !== '@' && preg_match('/\.theme-'.preg_quote($slug, '/').'(?![a-z0-9-])/', $selector)) {
                $out[] = $selector.' '.$block;
            }
            $i = $j;
        }

        return implode("\n\n", $out).($out ? "\n" : '');
    }
}
