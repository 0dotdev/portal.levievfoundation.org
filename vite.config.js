import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/filament/dashboard/theme.css',
            ],
            refresh: true,
        }),
    ],
}); 