import { defineConfig } from 'vite';
import { resolve } from 'path';
import { glob } from 'glob';
import autoprefixer from 'autoprefixer';
import purgecss from '@fullhuman/postcss-purgecss';

// Get all component SCSS files
const componentScssFiles = glob.sync('components/**/*.scss');
const componentJsFiles = glob.sync('components/**/*.js');

// PurgeCSS configuration (production only)
const purgeCSSConfig = {
  content: [
    './templates/**/*.twig',
    './templates/**/*.html.twig',
    './components/**/*.twig',
    './src/js/**/*.js',
    './components/**/*.js',
    './js/**/*.js',
    '../../../contrib/radix/templates/**/*.twig',
  ],
  safelist: {
    standard: [
      /^show$/,
      /^active$/,
      /^fade$/,
      /^collapsing$/,
      /^disabled$/,
      /^hidden$/,
      /^visible$/,
      'page-title',
      'title',
      'section-title',
    ],
    deep: [
      /^modal/,
      /^dropdown/,
      /^collapse/,
      /^offcanvas/,
      /^tooltip/,
      /^popover/,
      /^nav-/,
      /^navbar/,
      /^btn-/,
      /^alert-/,
      /^breadcrumb/,
      /^saho-/,
      /^drupal-/,
      /^js-/,
      /^form-/,
      /^is-/,
      /^has-/,
      /^accordion/,
      /^social-/,
      /^better-/,
      /^card/,
      /^container/,
      /^row$/,
      /^col/,
      /^g-/,
      /^m[tblrxy]?-/,
      /^p[tblrxy]?-/,
      /^text-/,
      /^bg-/,
      /^d-/,
      /^flex-/,
      /^align-/,
      /^justify-/,
      /^search-/,
      /^item-list/,
      /^clearfix/,
      /^path-/,
      /^views-/,
      /^pager/,
      /^page-/,
      /^pagination/,
    ],
    greedy: [
      /tooltip/,
      /popover/,
      /bs-/,
    ],
  },
  variables: true,
  keyframes: true,
  defaultExtractor: content => {
    const matches = content.match(/[\w-/:[\]]+(?<!:)/g) || [];
    return matches;
  },
};

// Build input entries for Rollup
const input = {
  'css/main.style': resolve(__dirname, 'src/scss/main.style.scss'),
  'css/saho-colors': resolve(__dirname, 'src/scss/base/_saho-colors.scss'),
  'js/main.script': resolve(__dirname, 'src/js/main.script.js'),
  // Page-specific CSS bundles for code splitting (using JS wrappers to emit CSS)
  'js/pages/landing-pages': resolve(__dirname, 'src/js/pages/landing-pages.js'),
  'js/pages/search-results': resolve(__dirname, 'src/js/pages/search-results.js'),
  'js/pages/article-layout': resolve(__dirname, 'src/js/pages/article-layout.js'),
};

// Add component SCSS files - output to dist/components/...
componentScssFiles.forEach(file => {
  const name = file.replace(/\.scss$/, '');
  input[name] = resolve(__dirname, file);
});

// Add component JS files - output to dist/components/...
componentJsFiles.forEach(file => {
  const name = file.replace(/\.js$/, '');
  input[name] = resolve(__dirname, file);
});

export default defineConfig(({ mode }) => {
  const isProduction = mode === 'production';

  return {
    // Base public path
    base: '/themes/custom/saho/dist/',

    // Build configuration
    build: {
      // Output directory - must be a subdirectory, not root
      outDir: 'dist',
      // Don't empty outDir on rebuild
      emptyOutDir: true,
      // Generate manifest for cache busting
      manifest: false,
      // Rollup options
      rollupOptions: {
        input,
        output: {
          // Output JS files
          entryFileNames: '[name].js',
          chunkFileNames: 'js/chunks/[name]-[hash].js',
          // Output CSS files (handled by CSS extraction)
          assetFileNames: (assetInfo) => {
            if (assetInfo.name?.endsWith('.css')) {
              return '[name][extname]';
            }
            return 'assets/[name]-[hash][extname]';
          },
        },
      },
      // CSS code splitting
      cssCodeSplit: true,
      // Minification
      minify: isProduction ? 'esbuild' : false,
      // Source maps in development
      sourcemap: !isProduction,
      // Target modern browsers
      target: 'es2020',
    },

    // CSS configuration
    css: {
      // PostCSS plugins
      postcss: {
        plugins: [
          autoprefixer(),
          ...(isProduction ? [purgecss(purgeCSSConfig)] : []),
        ],
      },
      // SCSS options
      preprocessorOptions: {
        scss: {
          // Silence deprecation warnings from dependencies
          silenceDeprecations: ['legacy-js-api', 'import', 'global-builtin', 'color-functions'],
          // Add node_modules to load path for ~ imports
          loadPaths: [resolve(__dirname, 'node_modules')],
        },
      },
      // Dev source maps
      devSourcemap: true,
    },

    // Development server
    server: {
      // Proxy to Drupal
      proxy: {
        // Proxy all requests except Vite's own
      },
      // Watch for changes
      watch: {
        usePolling: false,
      },
      // HMR configuration
      hmr: {
        overlay: true,
      },
    },

    // Resolve aliases
    resolve: {
      alias: {
        '@': resolve(__dirname, 'src'),
        '@components': resolve(__dirname, 'components'),
        '~': resolve(__dirname, 'node_modules'),
      },
    },

    // Plugins
    plugins: [
      // Copy static assets
      {
        name: 'copy-assets',
        buildStart() {
          // Assets are copied via separate script or Drupal handles them
        },
      },
    ],

    // Esbuild options for production
    esbuild: {
      drop: isProduction ? ['console', 'debugger'] : [],
    },
  };
});
