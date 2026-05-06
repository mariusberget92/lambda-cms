import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { existsSync, statSync } from 'fs';

// Resolves @/components/* and @/Components/* case-insensitively so imports
// written on Windows (case-insensitive FS) work unchanged on Linux.
function caseInsensitiveComponentsPlugin() {
    const jsRoot = resolve(__dirname, 'resources/js');
    return {
        name: 'case-insensitive-components',
        enforce: 'pre',
        resolveId(source) {
            // Handle both raw @/ paths and already-alias-resolved absolute paths
            let rest = null;
            const atMatch = source.match(/^@\/[cC]omponents\/(.*)/);
            if (atMatch) {
                rest = atMatch[1];
            } else {
                const absMatch = source.match(new RegExp(
                    jsRoot.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '/[cC]omponents/(.*)'
                ));
                if (absMatch) rest = absMatch[1];
            }
            if (!rest) return null;
            const isFile = (p) => existsSync(p) && statSync(p).isFile();
            for (const dir of ['Components', 'components']) {
                const full = resolve(jsRoot, dir, rest);
                if (isFile(full)) return full;
                for (const idx of ['index.js', 'index.ts']) {
                    const withIndex = resolve(jsRoot, dir, rest, idx);
                    if (isFile(withIndex)) return withIndex;
                }
            }
            return null;
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
        caseInsensitiveComponentsPlugin(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            'storage': resolve(__dirname, 'storage/app/public/'),
        },
    },
});
