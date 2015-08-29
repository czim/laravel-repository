<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Query\Builder;

class OrderBy extends AbstractCriteria
{
    const DEFAULT_DIRECTION = 'asc';

    /**
     * @var array
     */
    protected $orderClauses = [];

    /**
     * @param string|array  $columnOrArray     may be either a single column, in which the second parameter
     *                                         is used for direction, or an array of 'column' => 'direction' values
     * @param string        $direction         'asc'/'desc'
     */
    public function __construct($columnOrArray, $direction = self::DEFAULT_DIRECTION)
    {
        if ( ! is_array($columnOrArray)) {
            $columnOrArray = [ $columnOrArray => $direction ];

        } else {
            // make sure it is a proper array

            $newColumns = [];

            foreach ($columnOrArray as $column => $direction) {

                if (is_numeric($column)) {
                    $column    = $direction;
                    $direction = self::DEFAULT_DIRECTION;
                }

                $newColumns[$column] = $direction;
            }

            $columnOrArray = $newColumns;
        }

        $this->orderClauses = $columnOrArray;
    }


    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        foreach ($this->orderClauses as $column => $direction) {

            $model = $model->orderBy($column, $direction);
        }

        return $model;
    }

}
