<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Closure;
use Illuminate\Database\Eloquent\Builder;


class Has extends AbstractCriteria
{
    /**
     * @var string
     */
    protected $relation;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var string
     */
    protected $boolean;

    /**
     * @var Closure
     */
    protected $callback;


    /**
     * @param string  $relation
     * @param string  $operator
     * @param int     $count
     * @param string  $boolean
     * @param Closure $callback
     */
    public function __construct($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
    {
        $this->relation = $relation;
        $this->callback = $callback;
        $this->operator = $operator;
        $this->count    = $count;
        $this->boolean  = $boolean;
    }


    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        return $model->has($this->relation, $this->operator, $this->count, $this->boolean, $this->callback);
    }

}
