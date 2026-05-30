import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/site.css',
                'resources/css/pages/home.css',
                'resources/js/site.js',
            ],
            refresh: false,
        }),
        tailwindcss(),
    ],
    server: {
        hmr: {
            overlay: false,
        },
        watch: {
            usePolling: true,
            interval: 100,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});