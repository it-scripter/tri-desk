import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// No Tailwind and no external font provider: the project uses plain SCSS and
// serves every asset itself (CLAUDE.md, specification chapter 9 on privacy).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
