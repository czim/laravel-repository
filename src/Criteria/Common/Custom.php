<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Closure;
use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class Custom extends AbstractCriteria
{
    public function __construct(protected Closure $query)
    {
    }

    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        $callable = $this->query;

        return $callable($model);
    }
}
