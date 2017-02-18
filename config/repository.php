<?php

return [

    'generate' => [

        // The namespace in which generated repositories are placed.
        'namespace' => 'App\\Repositories',

        // The base repository class to extend.
        'base' => Czim\Repository\BaseRepository::class,

        // The suffix expected in a given repository class name.
        // This will be subtracted to determine the related model name.
        'suffix' => 'Repository',

        // The locations where Eloquent models are stored.
        'models' => 'App',
    ],

    // The default number of items per page when paginating results
    'perPage' => 1

];
