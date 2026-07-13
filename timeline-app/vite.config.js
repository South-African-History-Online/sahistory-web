import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

// Builds straight into the Drupal module with FIXED file names - the
// saho_timeline/timeline-app library references dist/timeline.js and
// dist/timeline.css; cache busting is the library's version key, bumped
// per release. emptyOutDir guarantees stale bundles cannot accumulate
// (the old app shipped ~45 orphaned hashed bundles).
export default defineConfig({
  plugins: [svelte()],
  build: {
    outDir: '../webroot/modules/custom/saho_timeline/dist',
    emptyOutDir: true,
    target: 'es2019',
    rollupOptions: {
      input: 'src/main.js',
      output: {
        // IIFE: Drupal attaches this as a classic script; an ES-module
        // build would leak top-level declarations into global scope and
        // collide (observed: "Identifier 'se' has already been declared").
        format: 'iife',
        entryFileNames: 'timeline.js',
        assetFileNames: (assetInfo) =>
          assetInfo.names?.some((n) => n.endsWith('.css')) ? 'timeline.css' : '[name][extname]',
      },
    },
  },
});
