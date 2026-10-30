const mix = require('laravel-mix');
const path = require('path');

// Повторяем сборку при изменении Blade, SCSS или JS.
const watchBlade = {
    apply(compiler) {
        compiler.hooks.afterCompile.tap('WatchBladeTemplates', (compilation) => {
            compilation.contextDependencies.add(path.resolve(__dirname, 'resources/views'));
        });
    },
};

// Bootstrap + собственный SCSS; jQuery + Bootstrap JS. Выход — public.
mix.js('resources/js/index.js', 'public/js')
    .sass('resources/sass/styles.scss', 'public/css')
    .options({ processCssUrls: false })
    .disableNotifications()
    .version()
    .webpackConfig({ plugins: [watchBlade] });
