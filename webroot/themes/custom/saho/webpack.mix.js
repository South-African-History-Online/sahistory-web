/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your application. See https://github.com/JeffreyWay/laravel-mix.
 |
*/
require('dotenv').config({ path: '.env.local' });
const mix = require('laravel-mix');
const glob = require('glob');
require('laravel-mix-stylelint');
require('laravel-mix-copy-watched');

/*
  |--------------------------------------------------------------------------
  | Configuration
  |--------------------------------------------------------------------------
*/
mix
  .disableNotifications()
  .options({
    processCssUrls: false,
  });

// Production-specific optimizations
if (mix.inProduction()) {
  mix
    // .version() // Enable versioning for cache busting (temporarily disabled)
    .options({
      processCssUrls: false,
      terser: {
        terserOptions: {
          compress: {
            drop_console: true, // Remove console.logs in production
            drop_debugger: true,
            pure_funcs: ['console.log', 'console.info'], // Remove specific console methods
          },
          output: {
            comments: false, // Remove all comments
          },
        },
        extractComments: false, // Don't create separate LICENSE files
      },
      cssNano: {
        preset: ['default', {
          discardComments: {
            removeAll: true,
          },
          normalizeWhitespace: true,
          colormin: true,
          minifyFontValues: true,
          minifySelectors: true,
        }],
      },
    })
    .webpackConfig({
      optimization: {
        providedExports: true,
        usedExports: true,
        sideEffects: true,
        minimize: true,
      },
    });
} else {
  // Development: Enable source maps
  mix
    .sourceMaps()
    .webpackConfig({
      devtool: 'source-map',
    });
}

/*
  |--------------------------------------------------------------------------
  | Browsersync
  |--------------------------------------------------------------------------
*/
mix.browserSync({
  proxy: process.env.DRUPAL_BASE_URL,
  files: [
    'components/**/*.css',
    'components/**/*.js',
    'components/**/*.twig',
    'templates/**/*.twig',
  ],
  stream: true,
});

/*
  |--------------------------------------------------------------------------
  | SASS with PurgeCSS (Production only)
  |--------------------------------------------------------------------------
*/
const purgecss = require('@fullhuman/postcss-purgecss').default;

// Configure PurgeCSS
const purgeCSSConfig = {
  content: [
    './templates/**/*.twig',
    './templates/**/*.html.twig',
    './components/**/*.twig',
    './src/js/**/*.js',
    './components/**/*.js',
    './js/**/*.js',
    // Include Radix base theme templates that might be used
    '../../../contrib/radix/templates/**/*.twig',
  ],
  // Safelist: CSS classes that should never be purged
  safelist: {
    // Standard patterns
    standard: [
      /^show$/,
      /^active$/,
      /^fade$/,
      /^collapsing$/,
      /^disabled$/,
      /^hidden$/,
      /^visible$/,
    ],
    // Deep patterns (including children)
    deep: [
      /^modal/,        // Bootstrap modals
      /^dropdown/,     // Bootstrap dropdowns
      /^collapse/,     // Bootstrap collapse
      /^offcanvas/,    // Bootstrap offcanvas
      /^tooltip/,      // Bootstrap tooltips
      /^popover/,      // Bootstrap popovers
      /^nav-/,         // Navigation classes
      /^navbar/,       // Navbar classes
      /^btn-/,         // Button variants
      /^alert-/,       // Alert variants
      /^breadcrumb/,   // Breadcrumb navigation
      /^saho-/,        // All custom SAHO classes
      /^drupal-/,      // Drupal-specific classes
      /^js-/,          // JavaScript-added classes
      /^form-/,        // Form elements
      /^is-/,          // State classes
      /^has-/,         // State classes
      /^accordion/,    // Accordion components
      /^social-/,      // Social sharing buttons
      /^better-/,      // Better social sharing
      /^card/,         // Card components
      /^container/,    // Container classes
      /^row$/,         // Grid row
      /^col/,          // Grid columns
      /^g-/,           // Grid gap utilities
      /^m[tblrxy]?-/,  // Margin utilities
      /^p[tblrxy]?-/,  // Padding utilities
      /^text-/,        // Text utilities
      /^bg-/,          // Background utilities
      /^d-/,           // Display utilities
      /^flex-/,        // Flexbox utilities
      /^align-/,       // Alignment utilities
      /^justify-/,     // Justification utilities
    ],
    // Greedy patterns (matches class and all variants)
    greedy: [
      /tooltip/,
      /popover/,
      /bs-/,           // Bootstrap internal classes
    ],
  },
  // Variables and keyframes should also be kept
  variables: true,
  keyframes: true,
  // Custom extractor for better Twig and JS support
  defaultExtractor: content => {
    // Match word characters, hyphens, colons, slashes, and square brackets
    const matches = content.match(/[\w-/:[\]]+(?<!:)/g) || [];
    return matches;
  },
};

// Main stylesheet with PurgeCSS in production
mix.sass("src/scss/main.style.scss", "build/css/main.style.css")
  .options({
    postCss: [
      require('autoprefixer'),
      ...(mix.inProduction() ? [purgecss(purgeCSSConfig)] : [])
    ]
  });

// Component stylesheets (also with PurgeCSS)
for (const sourcePath of glob.sync("components/**/*.scss")) {
  const destinationPath = sourcePath.replace(/\.scss$/, ".css");
  mix.sass(sourcePath, destinationPath)
    .options({
      postCss: [
        require('autoprefixer'),
        ...(mix.inProduction() ? [purgecss(purgeCSSConfig)] : [])
      ]
    });
}

/*
  |--------------------------------------------------------------------------
  | JS
  |--------------------------------------------------------------------------
*/
mix.js("src/js/main.script.js", "build/js/main.script.js");

for (const sourcePath of glob.sync("components/**/_*.js")) {
  const destinationPath = sourcePath.replace(/\/_([^/]+\.js)$/, "/$1");
  mix.js(sourcePath, destinationPath);
}

/*
  |--------------------------------------------------------------------------
  | Style Lint
  |--------------------------------------------------------------------------
*/
mix.stylelint({
  configFile: './.stylelintrc.json',
  context: './src',
  failOnError: false,
  files: ['**/*.scss'],
  quiet: false,
  customSyntax: 'postcss-scss',
});

/*
  |--------------------------------------------------------------------------
  * IMAGES / ICONS / VIDEOS / FONTS
  |--------------------------------------------------------------------------
  */
// * Directly copies the images, icons and fonts with no optimizations on the images
mix.copyDirectoryWatched('src/assets/images', 'build/assets/images');
mix.copyDirectoryWatched('src/assets/icons', 'build/assets/icons');
mix.copyDirectoryWatched('src/assets/videos', 'build/assets/videos');
mix.copyDirectoryWatched('src/assets/fonts/**/*', 'build/fonts');
