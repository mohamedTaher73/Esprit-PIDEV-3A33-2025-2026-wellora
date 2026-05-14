const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
        .setPublicPath('/build')
        // only needed for CDN's or subdirectory deploy
        //.setManifestKeyPrefix('build/')

        /*
         * ENTRY CONFIG
         */
        .addEntry('app', './assets/app.js')
        .addEntry('fitness', './assets/js/fitness.js')
        .addEntry('fitness-js', './assets/js/fitness.js')
        .addStyleEntry('fitness-css', './assets/styles/fitness.css')
        .addEntry('admin', './assets/admin.js')
        .addEntry('health-analytics', './assets/js/health-analytics.js')
        .addEntry('health-calendar', './assets/js/health-calendar.js')
        .addEntry('doctor-dashboard', './assets/js/doctor-dashboard.js')
        .addEntry('doctor-schedule', './assets/js/doctor-schedule.js')
        .addEntry('accessibility', './assets/js/accessibility.js')
        .addEntry('appointment-booking', './assets/js/appointment-booking.js')
        .addEntry('teleconsultation', './assets/js/teleconsultation.js')
        .addEntry('appointment-analytics', './assets/js/appointment-analytics.js')
        .addEntry('nutrition', './assets/js/nutrition-tracker.js')
        .addEntry('nutrition-goals', './assets/js/nutrition-goals.js')
        .addEntry('food-logging', './assets/js/food-logging.js')
        .addEntry('barcode-scanner', './assets/js/barcode-scanner.js')
        .addEntry('meal-planner', './assets/js/nutrition-planner.js')
        .addEntry('nutritionniste-dashboard', './assets/js/nutritionniste-dashboard.js')
        .addEntry('trail-community', './assets/js/trail-community.js')
        .addStyleEntry('trail-community-css', './assets/styles/trail-community.css')
        .addEntry('trail-maps', './assets/js/trail-maps.js')
        .addStyleEntry('trail-maps-css', './assets/styles/trail-maps.css')
        .addEntry('trail-analytics', './assets/js/trail-analytics.js')
        .addStyleEntry('trail-analytics-css', './assets/styles/trail-analytics.css')
        .addEntry('coach', './assets/js/coach.js')
        .addStyleEntry('coach-css', './assets/styles/coach.css')
        .addEntry('trail', './assets/js/trail.js')
        .addStyleEntry('trail-css', './assets/styles/trail.css')
        .addStyleEntry('analytics', './assets/styles/analytics.css')
        .addStyleEntry('appointment', './assets/styles/appointment.css')
        .addStyleEntry('schedule-css', './assets/styles/doctor-schedule.css')
        .addStyleEntry('teleconsultation-css', './assets/styles/teleconsultation.css')
        .addStyleEntry('analytics-dashboard', './assets/styles/analytics-dashboard.css')
        .addStyleEntry('nutrition-css', './assets/styles/nutrition.css')

        // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
        .splitEntryChunks()

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

        // will require an extra script tag for runtime.js
        // but, you probably want this, unless you're building a single-page app
        .enableSingleRuntimeChunk()

        /*
         * FEATURE CONFIG
         */
        .cleanupOutputBeforeBuild()
    // Build notifications can fail in restricted environments (e.g. sandboxed CI)
    // and are not required for a professional frontend build.
    // .enableBuildNotifications()
        .enableSourceMaps(!Encore.isProduction())
        // enables hashed filenames (e.g. app.abc123.css)
        .enableVersioning(Encore.isProduction())

        .configureBabel((config) => {
            config.plugins.push('@babel/plugin-proposal-class-properties');
        })

        // enables @babel/preset-env polyfills
        .configureBabelPresetEnv((config) => {
            config.useBuiltIns = 'usage';
            config.corejs = 3;
        })

        // enables Sass/SCSS support
        .enableSassLoader()

        // Enable PostCSS loader
        .enablePostCssLoader()

        // Copy static assets
        .copyFiles({
            from: './assets/images',
            to: 'images/[path][name].[hash:8].[ext]',
            pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/
        })
    ;

    module.exports = Encore.getWebpackConfig();
