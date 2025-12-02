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
        port: 3000,
        strictPort: true,
        hmr: {
            host: process.env.DDEV_HOSTNAME || 'localhost',
            protocol: process.env.DDEV_HOSTNAME ? 'wss' : 'ws',
            clientPort: process.env.DDEV_HOSTNAME ? 3001 : 3000,
        },
    },
});
