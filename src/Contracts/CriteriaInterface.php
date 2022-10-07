<?php

namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TRelated of \Illuminate\Database\Eloquent\Model
 */
interface CriteriaInterface
{
    /**
     * @param TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel> $model
     * @param BaseRepositoryInterface<TModel>                                   $repository
     * @return TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel>
     */
    public function apply(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model,
        BaseRepositoryInterface $repository,
    ): Model|Relation|DatabaseBuilder|EloquentBuilder;
}
