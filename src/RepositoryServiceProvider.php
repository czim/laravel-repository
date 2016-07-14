<?php

namespace Czim\Repository;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Commands provided by the current package.
     *
     * @var array
     */
    private $commands = [
        \Czim\Repository\Console\Commands\RepositoryMakeCommand::class,
    ];

    /**
     * The base package path.
     *
     * @var string
     */
    public static $packagePath = null;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        self::$packagePath = dirname(__DIR__);

        $this->publishes(
            [
                self::$packagePath . '/config/repository.php' => config_path('repository.php'),
            ],
            'repository'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
}
