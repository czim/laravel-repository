<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

/**
 * Applies a SINGLE scope.
 */
class Scope extends AbstractCriteria
{
    /**
     * @param string  $scope
     * @param mixed[] $parameters
     */
    public function __construct(
        protected string $scope,
        protected array $parameters = [],
    ) {
    }

    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        return call_user_func_array(
            [$model, $this->scope],
            $this->parameters
        );
    }
}
