<?php
namespace Czim\Repository;

use Czim\Repository\Console\Commands\MakeRepositoryCommand;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{

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
        $this->commands([
            MakeRepositoryCommand::class,
        ]);
    }

}
