import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/landing.js', 'resources/css/filament/commerce/theme.css'],
            refresh: true,
        }),
    ],
});
