<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Czim\Repository\Contracts\CriteriaInterface;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TRelated of \Illuminate\Database\Eloquent\Model
 *
 * @implements CriteriaInterface<TModel, TRelated>
 */
abstract class AbstractCriteria implements CriteriaInterface
{
    /**
     * @var BaseRepositoryInterface<TModel>
     */
    protected BaseRepositoryInterface $repository;

    /**
     * @param TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel> $model
     * @param BaseRepositoryInterface<TModel>                                   $repository
     * @return TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel>
     */
    public function apply(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model,
        BaseRepositoryInterface $repository,
    ): Model|Relation|EloquentBuilder|DatabaseBuilder {
        $this->repository = $repository;

        return $this->applyToQuery($model);
    }

    /**
     * @param TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel> $model
     * @return TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel>
     */
    abstract protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder;
}
