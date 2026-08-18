// Standalone build of the package assets: `npm install && npm run build` writes
// public/build/devboard.css and public/build/devboard.js (no hashes, no manifest), which the
// host app publishes with `php artisan vendor:publish --tag=devboard-assets` and includes through
// <x-devboard::assets /> when config('devboard.assets') === 'precompiled'.
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: false,
        rollupOptions: {
            input: 'resources/js/standalone.js',
            output: {
                entryFileNames: 'devboard.js',
                chunkFileNames: 'devboard-[name].js',
                assetFileNames: 'devboard.[ext]',
            },
        },
    },
});
