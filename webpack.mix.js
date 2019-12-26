const mix = require('laravel-mix');

mix.sass('resources/styles/module.scss', 'resources/build/module.min.css').options({
    postCss: [
        require('autoprefixer')(),
    ]
});

mix.scripts([
    'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.js',
    'node_modules/blueimp-tmpl/js/tmpl.js',
    'node_modules/jquery-imagemapster/dist/jquery.imagemapster.js',
    'node_modules/mobile-detect/mobile-detect.js',
    'resources/scripts/lib/jquery.imgareaselect.js',
], 'resources/build/vendor.min.js');

mix.babel([
    'resources/scripts/jquery.imagemapster.custom.js',
    'resources/scripts/module.js',
], 'resources/build/module.min.js');