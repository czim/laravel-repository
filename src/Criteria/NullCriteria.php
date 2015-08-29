<?php
namespace Czim\Repository\Criteria;

use Czim\Repository\Criteria\AbstractCriteria;

/**
 * This majestically does nothing.
 * May be used as a placeholder for 'removing'/'overruling' criteria by key
 * to disable normal keyed functionality such as for CriteriaKey::Order and such.
 */
class NullCriteria extends AbstractCriteria
{

    /**
     * @param $model
     * @return mixed
     */
    protected function applyToQuery($model)
    {
        return $model;
    }
}
