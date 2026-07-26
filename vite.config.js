import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const usePolling = process.env.CHOKIDAR_USEPOLLING === 'true'
    || process.env.CHOKIDAR_USEPOLLING === '1';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Escucha en el contenedor; el navegador del host usa localhost:5173.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: process.env.VITE_DEV_SERVER_URL || 'http://localhost:5173',
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
        },
        watch: {
            // Bind mounts en Docker Desktop (Windows/macOS) no disparan inotify de forma fiable.
            usePolling,
            interval: 300,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
