const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');

mix
    .setResourceRoot(`/${process.env.section}-assets`)
    .setPublicPath(`public/${process.env.section}-assets`)
    .sass(`resources/mixes/${process.env.section}/sass/app.scss`,
        `public/${process.env.section}-assets/css`, {}, [
            tailwindcss(`resources/mixes/${process.env.section}/tailwind.config.js`),
        ]
    )
;
