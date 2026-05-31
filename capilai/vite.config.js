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
            refresh: false, // ← evita recargas por cambios en Blade/Antlers
        }),
        tailwindcss(),
    ],

    server: {
        hmr: {
            overlay: false, // ← evita full reloads por errores
        },

        watch: {
            usePolling: true,   // ← watcher estable
            interval: 200,

            ignored: [
                '**/storage/framework/views/**',
                '**/content/**',
                '**/resources/views/**',
                '**/resources/blueprints/**',
                '**/resources/fieldsets/**',
            ],
        },
    },
});