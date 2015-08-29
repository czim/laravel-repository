<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Query\Builder;

class IsActive extends AbstractCriteria
{
    /**
     * @var string
     */
    protected $column;


    /**
     * The column name to check for 'active' state
     *
     * @param string $column
     */
    public function __construct($column = 'active')
    {
        $this->column = $column;
    }

    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        return $model->where($this->column, true);
    }
}
