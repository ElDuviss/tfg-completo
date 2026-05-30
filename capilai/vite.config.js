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
            refresh: false, // ← DESACTIVA EL AUTO-REFRESH QUE ROMPE LOS REDIRECTS
        }),
        tailwindcss(),
    ],
    server: {
        hmr: {
            overlay: false, // ← EVITA FULL RELOADS
        },
        watch: {
            usePolling: true, // ← MÁS ESTABLE EN WSL
            interval: 100,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});