<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class Has extends AbstractCriteria
{
    public function __construct(
        protected string $relation,
        protected string $operator = '>=',
        protected int $count = 1,
        protected string $boolean = 'and',
        protected ?Closure $callback = null,
    ) {
    }

    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        return $model->has($this->relation, $this->operator, $this->count, $this->boolean, $this->callback);
    }
}
