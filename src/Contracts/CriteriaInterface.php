<?php

namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Watson\Rememberable\Query\Builder as RememberableBuilder;

interface CriteriaInterface
{
    /**
     * @param Model|Relation|DatabaseBuilder|EloquentBuilder|RememberableBuilder $model
     * @param BaseRepositoryInterface                                   $repository
     * @return Model|Relation|DatabaseBuilder|EloquentBuilder|RememberableBuilder
     */
    public function apply(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model,
        BaseRepositoryInterface $repository,
    ): Model|Relation|DatabaseBuilder|EloquentBuilder;
}
