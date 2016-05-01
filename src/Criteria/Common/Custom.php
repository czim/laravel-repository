<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Query\Builder;

class Custom extends AbstractCriteria
{
    /**
     * @var callable $query
     */
    protected $query;


    public function __construct(callable $query)
    {
        $this->query = $query;
    }

    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        $callable = $this->query;

        return $callable($model);
    }

}
