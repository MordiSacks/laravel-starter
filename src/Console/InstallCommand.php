<?php

namespace MordiSacks\LaravelStarter\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msls:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Mordi Sacks\'s starter kit';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Require composer dev packages
        static::updateComposerPackages(function ($packages) {
            return [
                    'barryvdh/laravel-ide-helper' => '^2.9',
                    'itsgoingd/clockwork'         => '^5.0',
                ] + $packages;
        });

        // NPM Packages...
        static::updateNodePackages(function ($packages) {
            return [
                    '@tailwindcss/forms' => '^0.2.1',
                    'alpinejs'           => '^2.7.3',
                    'autoprefixer'       => '^10.1.0',
                    'postcss'            => '^8.2.1',
                    'resolve-url-loader' => '^3.1.2',
                    'sass'               => '^1.32.6',
                    'sass-loader'        => '^11.0.0',
                    'tailwindcss'        => '^2.0.2',
                ] + $packages;
        });

        // replace webpack.mix.js
        copy(__DIR__ . '/../../stubs/webpack.mix.js', base_path('webpack.mix.js'));

        // remove default js/css
        (new Filesystem)->deleteDirectory(resource_path('css'));
        (new Filesystem)->deleteDirectory(resource_path('js'));

        // copy mixes
        (new Filesystem)->ensureDirectoryExists(resource_path('mixes/auth'));
        (new Filesystem)->copyDirectory(__DIR__ . '/../../stubs/resources/mixes/auth', resource_path('mixes/auth'));

        (new Filesystem)->ensureDirectoryExists(resource_path('mixes/app'));
        (new Filesystem)->copyDirectory(__DIR__ . '/../../stubs/resources/mixes/app', resource_path('mixes/app'));

        // copy views
        (new Filesystem)->ensureDirectoryExists(resource_path('views/auth'));
        (new Filesystem)->copyDirectory(__DIR__ . '/../../stubs/resources/views/auth', resource_path('views/auth'));

        // copy controllers
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers/Auth'));
        (new Filesystem)->copyDirectory(__DIR__ . '/../../stubs/App/Http/Controllers/Auth', app_path('Http/Controllers/Auth'));

        // copy routes and add to web
        copy(__DIR__ . '/../../stubs/routes/auth.php', base_path('routes/auth.php'));
        static::appendToFileIfNeeded(
            PHP_EOL . "require __DIR__.'/auth.php';" . PHP_EOL,
            base_path('routes/web.php')
        );
    }

    /**
     * Update "composer.json" packages
     *
     * @param callable $callback
     * @param bool     $dev
     *
     * @return void
     */
    protected static function updateComposerPackages(callable $callback, bool $dev = true)
    {
        if (!file_exists(base_path('composer.json'))) {
            return;
        }

        $configurationKey = $dev ? 'require-dev' : 'require';

        $packages = json_decode(file_get_contents(base_path('composer.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        // if dev required ide-helper, add post update commands
        if (
            isset($packages['require-dev'])
            && isset($packages['require-dev']['barryvdh/laravel-ide-helper'])
        ) {
            $packages['scripts'] = $packages['scripts'] ?? [];
            $packages['scripts']['post-update-cmd'] = $packages['scripts']['post-update-cmd'] ?? [];
            $packages['scripts']['post-update-cmd'] =
                [
                    'Illuminate\Foundation\ComposerScripts::postUpdate',
                    '@php artisan ide-helper:generate',
                    '@php artisan ide-helper:meta',
                ]
                + $packages['scripts']['post-update-cmd'];
        }

        file_put_contents(
            base_path('composer.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    /**
     * Update the "package.json" file.
     *
     * @param callable $callback
     * @param bool     $dev
     *
     * @return void
     */
    protected static function updateNodePackages(callable $callback, bool $dev = true)
    {
        if (!file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    /**
     * Replace a given string within a given file.
     *
     *
     * @param string $search
     * @param string $replace
     * @param string $path
     *
     * @return void
     */
    protected static function replaceInFile(string $search, string $replace, string $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Append a given string to a given file
     * if does not already exist.
     *
     *
     * @param string $string
     * @param string $path
     *
     * @return void
     */
    protected static function appendToFileIfNeeded(string $string, string $path)
    {
        $fh = fopen($path, 'r+');

        // Check if exists
        while (!feof($fh)) {
            $buffer = fgets($fh);
            if (strpos($buffer, trim($string)) === false) {
                continue;
            }

            fclose($fh);
            return;
        }

        // Append
        fwrite($fh, $string);

        fclose($fh);
    }
}
