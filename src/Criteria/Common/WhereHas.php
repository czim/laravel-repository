<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class WhereHas extends AbstractCriteria
{
    /**
     * @var string
     */
    protected $relation;

    /**
     * @var Closure
     */
    protected $callback;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var int
     */
    protected $count;


    /**
     * @param string  $relation
     * @param Closure $callback
     * @param string  $operator
     * @param int     $count
     */
    public function __construct($relation, Closure $callback, $operator = '>=', $count = 1)
    {
        $this->relation = $relation;
        $this->callback = $callback;
        $this->operator = $operator;
        $this->count = $count;
    }


    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        return $model->whereHas($this->relation, $this->callback, $this->operator, $this->count);
    }

}
