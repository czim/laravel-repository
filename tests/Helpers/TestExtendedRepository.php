<?php
namespace Czim\Repository\Test\Helpers;

use Czim\Repository\Criteria\NullCriteria;
use Czim\Repository\ExtendedRepository;
use Czim\Repository\Traits\FindsModelsByTranslationTrait;
use Czim\Repository\Traits\HandlesEloquentRelationManipulationTrait;
use Czim\Repository\Traits\HandlesEloquentSavingTrait;
use Czim\Repository\Traits\HandlesListifyModelsTrait;

class TestExtendedRepository extends ExtendedRepository
{
    use HandlesEloquentRelationManipulationTrait,
        HandlesEloquentSavingTrait,
        HandlesListifyModelsTrait,
        FindsModelsByTranslationTrait;

    // model needs an active check by default
    protected $hasActive = true;

    // test assumes cache is enabled by default
    protected $enableCache = true;


    public function model()
    {
        return TestExtendedModel::class;
    }


    public function defaultCriteria()
    {
        return collect([
            'TestDefault' => new NullCriteria(),
        ]);
    }
}


