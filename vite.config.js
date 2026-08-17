import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';


export default defineConfig({
    plugins: [
  
        laravel({
           input: [
    'resources/css/app.css',
    'resources/js/app.js',
    
],
            refresh: true,
            fonts: [
                bunny('Tajawal', {
                    weights: [400, 500, 700, 800],
                }),
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        react(),
    ],
    css: {
        lightningcss: {
            errorRecovery: true,
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
