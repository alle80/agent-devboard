<?php

namespace Alle80\Griglia\Http\Controllers;

use Alle80\Griglia\ThemeStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Serves the files of installed theme packs (CSS, images, fonts) from storage/app/themes. */
class ThemeAssetController
{
    public function __invoke(string $slug, string $path): BinaryFileResponse
    {
        abort_unless(ThemeStore::isValidSlug($slug) && ! str_contains($path, '..'), 404, __('griglia::t.errors.not_found'));
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(in_array($ext, ThemeStore::EXTENSIONS, true) && $ext !== 'json', 404, __('griglia::t.errors.not_found'));

        $file = ThemeStore::path($slug, $path);
        abort_unless(is_file($file), 404, __('griglia::t.errors.not_found'));

        $types = ['css' => 'text/css', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'md' => 'text/plain', 'txt' => 'text/plain'];

        return response()->file($file, [
            'Content-Type' => $types[$ext] ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
            // Theme assets are data, never documents: no scripts, no navigation side effects
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; font-src 'self'; sandbox",
        ]);
    }
}
