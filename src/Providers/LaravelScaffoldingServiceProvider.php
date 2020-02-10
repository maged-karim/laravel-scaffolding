<?php

namespace MagedKarim\LaravelScaffolding\Providers;

use Illuminate\Support\ServiceProvider;
use MagedKarim\LaravelScaffolding\Console\Commands\ScaffoldingInstallCommand;

class LaravelScaffoldingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ScaffoldingInstallCommand::class,
            ]);
        }
    }
}
