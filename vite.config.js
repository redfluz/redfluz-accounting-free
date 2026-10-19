import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/sass/themes.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
    },
});
