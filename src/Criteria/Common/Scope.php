<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;

/**
 * Applies a SINGLE scope
 */
class Scope extends AbstractCriteria
{

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var array
     */
    protected $parameters;


    /**
     * @param string $scope
     * @param array  $parameters
     */
    public function __construct($scope, array $parameters = [])
    {
        $this->scope      = $scope;
        $this->parameters = $parameters;
    }

    /**
     * @param $model
     * @return mixed
     */
    protected function applyToQuery($model)
    {
        call_user_func_array([ $model, $this->scope ], $this->parameters);

        return $model;
    }
}
