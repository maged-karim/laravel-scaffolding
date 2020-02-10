<?php

namespace MagedKarim\LaravelScaffolding\Preset;

use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;

class Scaffolding
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Scaffolding constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Install the preset.
     *
     * @return void
     */
    public function install()
    {
        $this->updateComposer();
        $this->updateGitignore();
        $this->copyFiles();
        $this->registerDashboardRoutesServiceProvider();
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected function updateRequireArray(array $packages)
    {
        $dependencies = [
                'davejamesmiller/laravel-breadcrumbs' => '^5.3',
                'elnooronline/laravel-bootstrap-forms' => '^2.2',
                'laraeast/laravel-settings' => '^1.0',
            ] + $packages;

        if ($this->config['multilingual']) {
            $dependencies['astrotomic/laravel-translatable'] = '^11.6';
        }

        return $dependencies;
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected function updateRequireDevArray(array $packages)
    {
        $dependencies = [
                'barryvdh/laravel-ide-helper' => '^2.6',
                'barryvdh/laravel-debugbar' => '^3.2',
                'friendsofphp/php-cs-fixer' => '^2.15',
            ] + $packages;

        if ($this->config['template'] == 'adminlte') {
            $dependencies['laraeast/laravel-adminlte'] = 'dev-master';
        }

        return $dependencies;
    }

    /**
     * Update the given scripts array.
     *
     * @param  array  $scripts
     * @return array
     */
    protected function updateScriptsArray(array $scripts)
    {
        return [
                'php-cs:issues' => 'vendor/bin/php-cs-fixer fix --diff --dry-run',
                'php-cs:fix' => 'vendor/bin/php-cs-fixer fix',
                'app:clear' => 'php artisan clear-compiled && php artisan cache:clear && php artisan config:clear && php artisan debugbar:clear && php artisan route:clear && php artisan view:clear',
                'auto-complete:generate' => [
                    '@php artisan ide-helper:meta --ansi --quiet',
                    '@php artisan ide-helper:generate --ansi --quiet',
                    '@php artisan ide-helper:models --nowrite --quie',
                ],
                'post-update-cmd' => [
                    'Illuminate\Foundation\ComposerScripts::postUpdate',
                    '@php artisan ide-helper:generate --ansi --quiet',
                    '@php artisan ide-helper:meta --ansi --quiet',
                ],
            ] + $scripts;
    }

    /**
     * Update the "composer.json" file.
     *
     * @return void
     */
    protected function updateComposer()
    {
        if (! file_exists(base_path('composer.json'))) {
            return;
        }

        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $composer['require'] = $this->updateRequireArray(
            array_key_exists('require', $composer) ? $composer['require'] : []
        );

        $composer['require-dev'] = $this->updateRequireDevArray(
            array_key_exists('require-dev', $composer) ? $composer['require-dev'] : []
        );

        $composer['scripts'] = $this->updateScriptsArray(
            array_key_exists('scripts', $composer) ? $composer['scripts'] : []
        );

        ksort($composer['require']);
        ksort($composer['require-dev']);

        file_put_contents(
            base_path('composer.json'),
            json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    protected function updateGitignore()
    {
        $filesystem = new Filesystem;

        $gitignore = array_filter(explode(PHP_EOL, $filesystem->get(base_path('.gitignore'))));

        $gitignore[] = '.idea';
        $gitignore[] = '/storage/debugbar';
        $gitignore[] = '.php_cs.cache';

        ksort($gitignore);

        file_put_contents(
            base_path('.gitignore'), implode(PHP_EOL, array_unique($gitignore))
        );
    }

    protected function copyFiles()
    {
        copy(
            __DIR__ . '/stubs/database/migrations/2020_02_10_194515_create_settings_table.php',
            database_path('migrations/2020_02_10_194515_create_settings_table.php')
        );
        copy(__DIR__ . '/stubs/.gitlab-ci.yml', base_path('.gitlab-ci.yml'));
        copy(__DIR__ . '/stubs/.php_cs', base_path('.php_cs'));
    }

    /**
     * Register the Dashboard routes service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerDashboardRoutesServiceProvider()
    {
        $namespace = Container::getInstance()->getNamespace();

        $namespace = Str::replaceLast('\\', '', $namespace);

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace . '\\Providers\\DashboardRouteServiceProvider::class')) {
            return;
        }

        copy(
            __DIR__ . '/stubs/Providers/DashboardRouteServiceProvider.stub',
            app_path('Providers/DashboardRouteServiceProvider.php')
        );

        copy(
            __DIR__ . '/stubs/routes/dashboard.php',
            base_path('routes/dashboard.php')
        );

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class," . PHP_EOL,
            "{$namespace}\\Providers\RouteServiceProvider::class," . PHP_EOL . "        {$namespace}\Providers\DashboardRouteServiceProvider::class," . PHP_EOL,
            $appConfig
        ));

        file_put_contents(app_path('Providers/DashboardRouteServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/DashboardRouteServiceProvider.php'))
        ));
    }
}
