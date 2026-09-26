/**
 * This file is part of DixlasePages.
 *
 * Copyright (C) 2025 {author}
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    build: {
        outDir: path.resolve(__dirname, 'resources/assets'),
        emptyOutDir: false,
        copyPublicDir: false,
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'resources/src/js/app.js'),
                style: path.resolve(__dirname, 'resources/src/css/style.scss'),
            },
            output: {
                // Content-hash JS/CSS entry filenames so each build
                // that changes bytes gets a new URL — automatic cache
                // busting for browsers that already cached the prior
                // asset. Identical builds still hash to the same
                // filename (Vite's `[hash]` is a content hash, not a
                // build timestamp), so no-op rebuilds do not churn
                // URLs. Fonts / images stay stable to keep the browser
                // font cache warm across rebuilds.
                //
                // See dixlase Sep 2026 incident: pre-hash stable URLs
                // let iOS Safari cache pre-refactor CSS indefinitely,
                // and "Clear History and Website Data" did not help.
                entryFileNames: 'js/[name]-[hash].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: 'css/[name]-[hash][extname]',
            },
        },
    },
    css: {
        // Anchor PostCSS to this repo so Vite does not walk up and
        // pick up the Dixlase Core `postcss.config.js` (which requires
        // Tailwind — absent from the plugin ZIP and unavailable when
        // building against the release core). Empty object = no
        // PostCSS plugins for this plugin's build.
        postcss: {},
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
            },
        },
    },
});
