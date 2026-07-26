import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Docker Desktop + bind mount: los eventos nativos suelen fallar.
            // CHOKIDAR_USEPOLLING=true en docker-compose también aplica a `vite build --watch`.
            usePolling: process.env.CHOKIDAR_USEPOLLING === 'true',
            interval: 300,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
