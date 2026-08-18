<?php

namespace Alle80\Devboard\Http\Controllers;

use Alle80\Devboard\ThemeStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Serves the files of installed theme packs (CSS, images, fonts) from storage/app/themes. */
class ThemeAssetController
{
    public function __invoke(string $slug, string $path): BinaryFileResponse
    {
        abort_unless(ThemeStore::isValidSlug($slug) && ! str_contains($path, '..'), 404);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(in_array($ext, ThemeStore::EXTENSIONS, true) && $ext !== 'json', 404);

        $file = ThemeStore::path($slug, $path);
        abort_unless(is_file($file), 404);

        $types = ['css' => 'text/css', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'md' => 'text/plain', 'txt' => 'text/plain'];

        return response()->file($file, ['Content-Type' => $types[$ext] ?? 'application/octet-stream', 'Cache-Control' => 'public, max-age=86400']);
    }
}
