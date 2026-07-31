import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// The skeleton's `fonts: [bunny('Instrument Sans', ...)]` option was removed: the site
// uses Inter + Manrope (loaded from Google Fonts in the layout), and bunny() hits the
// network at build time, which breaks offline builds.

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
