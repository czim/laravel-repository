<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

/**
 * This majestically does nothing.
 * May be used as a placeholder for 'removing'/'overruling' criteria by key
 * to disable normal keyed functionality such as for CriteriaKey::Order and such.
 */
class NullCriteria extends AbstractCriteria
{
    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        return $model;
    }
}
