<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Query\Builder;

class Take extends AbstractCriteria
{
    /**
     * @var int
     */
    protected $quantity;


    /**
     * The number of records returned
     *
     * @param int $quantity
     */
    public function __construct($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        return $model->take($this->quantity);
    }
}
