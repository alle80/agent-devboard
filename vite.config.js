// Standalone build of the package assets: `npm install && npm run build` writes
// public/build/griglia.css and public/build/griglia.js (no hashes, no manifest), which the
// host app publishes with `php artisan vendor:publish --tag=griglia-assets` and includes through
// <x-griglia::assets /> when config('griglia.assets') === 'precompiled'.
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
                entryFileNames: 'griglia.js',
                chunkFileNames: 'griglia-[name].js',
                assetFileNames: 'griglia.[ext]',
            },
        },
    },
});
