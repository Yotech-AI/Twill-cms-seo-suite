import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

// import.meta.dirname (not __dirname): this config is loaded as native ESM
// (package.json has "type": "module"), and Vite 8's native config loader
// warns that __dirname support there is deprecated. import.meta.dirname is
// Node's direct ESM equivalent (Node >= 20.11) and resolves to the exact
// same directory.
const __dirname = import.meta.dirname;

// Standalone build for the Twill SEO editor panel. Deliberately separate from
// a host app's own Vite setup: outputs a self-contained IIFE + css committed
// to resources/dist so adopters need no JS build step at all (AssetController
// serves the committed files straight from there). This mirrors the
// twill-cms-ai-assistent sibling's pattern, with its two known gaps fixed:
// a real package.json ships at the repo root, and outDir points at this
// package's own resources/dist rather than assuming it lives inside a host
// app's public/ directory.
//
// Build with: npm run build
export default defineConfig({
    plugins: [vue()],
    // Never copy a host app's public/ dir into our output.
    publicDir: false,
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
        __VUE_OPTIONS_API__: 'false',
        __VUE_PROD_DEVTOOLS__: 'false',
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
    },
    build: {
        outDir: resolve(__dirname, '../dist'),
        emptyOutDir: true,
        cssCodeSplit: false,
        lib: {
            entry: resolve(__dirname, 'main.js'),
            name: 'TwillSeo',
            formats: ['iife'],
            fileName: () => 'twill-seo.iife.js',
            cssFileName: 'twill-seo',
        },
    },
});
