<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Czim\Repository\Contracts\CriteriaInterface;

abstract class AbstractCriteria implements CriteriaInterface
{
    protected BaseRepositoryInterface $repository;

    public function apply(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model,
        BaseRepositoryInterface $repository,
    ): Model|Relation|EloquentBuilder|DatabaseBuilder {
        $this->repository = $repository;

        return $this->applyToQuery($model);
    }

    abstract protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder;
}
