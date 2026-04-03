import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/platform-marketing.css',
                'resources/js/platform-marketing.js',
                'resources/css/platform-admin.css',
                'resources/css/tenant-admin.css',
                'resources/css/booking-calendar.css',
                'resources/js/booking-calendar.js',
            ],
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
