<?php

declare(strict_types=1);

namespace Czim\Repository\Test\Helpers;

use Czim\Repository\Criteria\NullCriteria;
use Czim\Repository\ExtendedRepository;
use Czim\Repository\Traits\FindsModelsByTranslationTrait;
use Czim\Repository\Traits\HandlesEloquentRelationManipulationTrait;
use Czim\Repository\Traits\HandlesEloquentSavingTrait;
use Czim\Repository\Traits\HandlesListifyModelsTrait;
use Illuminate\Support\Collection;

class TestExtendedRepository extends ExtendedRepository
{
    use FindsModelsByTranslationTrait;
    use HandlesEloquentRelationManipulationTrait;
    use HandlesEloquentSavingTrait;
    use HandlesListifyModelsTrait;

    /**
     * model needs an active check by default
     *
     * @var bool
     */
    protected bool $hasActive = true;

    /**
     * test assumes cache is enabled by default
     *
     * @var bool
     */
    protected bool $enableCache = true;


    /**
     * {@inheritDoc}
     */
    public function model(): string
    {
        return TestExtendedModel::class;
    }

    /**
     * {@inheritDoc}
     */
    public function defaultCriteria(): Collection
    {
        return collect([
            'TestDefault' => new NullCriteria(),
        ]);
    }
}
