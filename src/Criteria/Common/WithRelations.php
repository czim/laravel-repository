<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TRelated of \Illuminate\Database\Eloquent\Model
 *
 * @extends AbstractCriteria<TModel, TRelated>
 */
class WithRelations extends AbstractCriteria
{
    /**
     * @param array<int|string, string|callable> $withStatements
     */
    public function __construct(protected array $withStatements)
    {
    }

    /**
     * @param TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel> $model
     * @return TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel>
     */
    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        return $model->with($this->withStatements);
    }
}
