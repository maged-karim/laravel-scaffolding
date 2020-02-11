<?php

namespace MagedKarim\LaravelScaffolding\Console\Commands;

use Illuminate\Console\Command;
use MagedKarim\LaravelScaffolding\Preset\Scaffolding;

class ScaffoldingInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scaffolding:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install fresh scaffolding for new laravel applications';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->assets();

        if (empty(glob(database_path('migrations/*_create_media_table*.*')))) {
            $this->call('vendor:publish', [
                '--provider' => 'Spatie\MediaLibrary\MediaLibraryServiceProvider',
                '--tag' => 'migrations',
                '--quiet' => true,
            ]);
        }
    }

    protected function assets()
    {
        $config = [
            'multilingual' => false,
            'template' => 'adminlte',
        ];

        if ($this->confirm('Is your application multilingual ?')) {
            $config['multilingual'] = true;
        }

        $template = $this->choice('Which templete you want to install ?', [
            'adminlte',
        ], 0);

        $config['template'] = $template;

        $scaffolding = new Scaffolding($config);

        $scaffolding->install();

        $this->info('Laravel scaffolding installed successfully.');

        if ($config['template'] == 'adminlte') {
            $this->comment('Please run "composer update && php artisan adminlte:install && npm install && npm run adminlte:dev" to install the scaffolding packages.');
        } else {
            $this->comment('Please run "composer update" to install the scaffolding packages.');
        }
    }
}
