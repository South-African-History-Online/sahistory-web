/**
 * @file
 * Laravel Mix configuration for SAHO Shop theme
 */

const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 */

mix
  // Compile SCSS to CSS
  .sass('src/scss/global.scss', 'css/global.css')
  .sass('src/scss/commerce.scss', 'css/commerce.css')

  // Compile JS
  .js('src/js/global.js', 'js/global.js')
  .js('src/js/commerce.js', 'js/commerce.js')

  // Options
  .options({
    processCssUrls: false,
    postCss: [
      require('autoprefixer')
    ]
  })

  // Source maps for development
  .sourceMaps(!mix.inProduction(), 'source-map');

// Disable notifications
mix.disableNotifications();
