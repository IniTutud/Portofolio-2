import { copyFileSync, cpSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

const projectRoot = fileURLToPath(new URL('.', import.meta.url));
const publicRoot = resolve(projectRoot, 'public');
const outputRoot = resolve(projectRoot, 'dist');

export default defineConfig({
    root: projectRoot,
    publicDir: false,
    plugins: [
        vue(),
        tailwindcss(),
        {
            name: 'copy-static-portfolio-assets',
            closeBundle() {
                cpSync(
                    resolve(publicRoot, 'images'),
                    resolve(outputRoot, 'images'),
                    { recursive: true },
                );

                for (const filename of ['favicon.ico', 'robots.txt']) {
                    copyFileSync(
                        resolve(publicRoot, filename),
                        resolve(outputRoot, filename),
                    );
                }
            },
        },
    ],
    build: {
        outDir: outputRoot,
        emptyOutDir: true,
    },
});
