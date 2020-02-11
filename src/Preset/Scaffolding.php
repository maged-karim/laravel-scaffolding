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
        $this->copyModelsTraits();
        $this->replaceUserNamespace();
        $this->updateUsersMigration();
        $this->addDashboardAccessMiddleware();
        $this->addFreshTokenMiddleware();
        $this->registerDashboardRoutesServiceProvider();
        $this->copyLangFiles();
        $this->replaceRouteRedirect();
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
                'calebporzio/parental' => '^0.9',
                'spatie/laravel-medialibrary' => '^7.6',
                'laravel/passport' => '^7.3',
                'doctrine/dbal' => '^2.9',
                'laracasts/presenter' => '^0.2.1',
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
                'martinlindhe/laravel-vue-i18n-generator' => '^0.1.42',
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

    protected function replaceUserNamespace()
    {
        $namespace = Container::getInstance()->getNamespace();
        $namespace = Str::replaceLast('\\', '', $namespace);
        $filesystem = new Filesystem;

        $files = $this->rglob(app_path('*.php'));
        $files = array_merge($files, $this->rglob(config_path('*.php')));
        $files = array_merge($files, $this->rglob(resource_path('*.php')));

        if ($user = app_path('Models/User.php')) {
            file_put_contents(
                $user,
                str_replace(
                    "namespace {$namespace};",
                    "namespace {$namespace}\Models;",
                    $filesystem->get($user)
                )
            );
        }

        foreach ($files as $file) {
            file_put_contents(
                $file,
                str_replace("{$namespace}\User", "{$namespace}\Models\User", $filesystem->get($file))
            );
        }

        if (file_exists(app_path('User.php'))) {
            $filesystem->delete(app_path('User.php'));
        }
    }

    protected function copyModelsTraits()
    {
        $filesystem = new Filesystem;

        if (! $filesystem->isDirectory($directory = app_path('Models/Helpers'))) {
            $filesystem->makeDirectory($directory, 0755, true);
        }
        if (! $filesystem->isDirectory($directory = app_path('Models/Concerns'))) {
            $filesystem->makeDirectory($directory, 0755, true);
        }
        copy(
            __DIR__ . '/stubs/Models/User.stub',
            app_path('Models/User.php')
        );
        copy(
            __DIR__ . '/stubs/Models/Helpers/UserHelpers.stub',
            app_path('Models/Helpers/UserHelpers.php')
        );
        copy(
            __DIR__ . '/stubs/Models/Concerns/HasMediaTrait.stub',
            app_path('Models/Concerns/HasMediaTrait.php')
        );
    }

    protected function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }

    protected function updateUsersMigration()
    {
        $file = $this->rglob(database_path('migrations/*_create_users_table.*'))[0];

        $filesystem = new Filesystem;

        if (Str::contains($filesystem->get($file), "\$table->string('type')->nullable();")) {
            return;
        }

        file_put_contents(
            $file,
            str_replace(
                "\$table->string('password');",
                "\$table->string('password');\n            \$table->string('type')->nullable();", $filesystem->get($file)
            )
        );
    }

    protected function addFreshTokenMiddleware()
    {
        $file = app_path('Http/Kernel.php');

        $filesystem = new Filesystem;

        if (! file_exists($file)) {
            return;
        }

        if (Str::contains($filesystem->get($file), 'CreateFreshApiToken')) {
            return;
        }

        file_put_contents(
            $file,
            str_replace(
                "\App\Http\Middleware\VerifyCsrfToken::class,",
                "\App\Http\Middleware\VerifyCsrfToken::class,\n            \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,", $filesystem->get($file)
            )
        );
    }
    protected function addDashboardAccessMiddleware()
    {
        $middlewarePath = app_path('Http/Middleware/DashboardAccessMiddleware.php');

        if (! file_exists($middlewarePath)) {
            copy(__DIR__.'/stubs/Middleware/DashboardAccessMiddleware.stub', $middlewarePath);
        }

        $file = app_path('Http/Kernel.php');

        $filesystem = new Filesystem;

        if (! file_exists($file)) {
            return;
        }

        if (Str::contains($filesystem->get($file), 'DashboardAccessMiddleware')) {
            return;
        }

        file_put_contents(
            $file,
            str_replace(
                "'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,",
                "'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,\n        'dashboard.access' => \App\Http\Middleware\DashboardAccessMiddleware::class,", $filesystem->get($file)
            )
        );
    }

    protected function copyLangFiles()
    {
        $filesystem = new Filesystem;

        if ($filesystem->isDirectory(resource_path('lang/ar'))) {
            return;
        }
        $filesystem->copyDirectory(__DIR__.'/stubs/resources/lang', resource_path('lang'));
    }

    protected function replaceRouteRedirect()
    {
        $file = app_path('Providers/RouteServiceProvider.php');

        $filesystem = new Filesystem;

        if (! file_exists($file)) {
            return;
        }

        file_put_contents(
            $file,
            str_replace(
                "public const HOME = '/home';",
                "public const HOME = '/dashboard';",
                $filesystem->get($file)
            )
        );
    }
}
