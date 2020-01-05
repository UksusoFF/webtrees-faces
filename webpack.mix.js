const mix = require('laravel-mix');

mix.sass('resources/styles/module.scss', 'resources/build/module.min.css').options({
    postCss: [
        require('autoprefixer')(),
    ]
});

mix.sass('resources/styles/admin/config.scss', 'resources/build/admin.min.css').options({
    postCss: [
        require('autoprefixer')(),
    ]
});

mix.scripts([
    'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.js',
    'node_modules/blueimp-tmpl/js/tmpl.min.js',
    'node_modules/imgareaselect/jquery.imgareaselect.js',
    'node_modules/jquery-imagemapster/dist/jquery.imagemapster.js',
    'node_modules/mobile-detect/mobile-detect.js',
], 'resources/build/vendor.min.js');

mix.babel([
    'resources/scripts/jquery.imagemapster.custom.js',
    'resources/scripts/module.js',
], 'resources/build/module.min.js');

mix.babel([
    'resources/scripts/admin/config.js',
], 'resources/build/admin.min.js');