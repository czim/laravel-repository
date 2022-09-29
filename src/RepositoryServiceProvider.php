<?php

declare(strict_types=1);

namespace Czim\Repository;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes(
            [
                dirname(__DIR__) . '/config/repository.php' => config_path('repository.php'),
            ],
            'repository'
        );
    }
}
