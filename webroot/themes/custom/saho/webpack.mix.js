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
  .sourceMaps()
  .webpackConfig({
    devtool: 'source-map',
  })
  .disableNotifications()
  .options({
    processCssUrls: false,
  });

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
  | SASS
  |--------------------------------------------------------------------------
*/
mix.sass("src/scss/main.style.scss", "build/css/main.style.css");

for (const sourcePath of glob.sync("components/**/*.scss")) {
  const destinationPath = sourcePath.replace(/\.scss$/, ".css");
  mix.sass(sourcePath, destinationPath);
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
