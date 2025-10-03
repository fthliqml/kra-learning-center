import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Production build does not need custom server / origin definitions. Keeping the config
// minimal avoids embedding hard-coded http origins that cause mixed content when the
// app is served behind an HTTPS tunnel (e.g., ngrok).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
