<?php
namespace Czim\Repository\Test\Helpers;

use Czim\Repository\Contracts\PostProcessorInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Just for testing the application of postProcessors in repositories
 * Simply adds an attribute to the models it affects.
 */
class TestPostProcessor implements PostProcessorInterface
{

    /**
     * @var string
     */
    protected $testParameter;

    /**
     * @var string
     */
    protected $testParameterValue;

    /**
     * @param string $testParameter
     * @param string $testParameterValue
     */
    public function __construct($testParameter, $testParameterValue)
    {
        $this->testParameter      = $testParameter;
        $this->testParameterValue = $testParameterValue;
    }

    /**
     * Applies processing to a single model
     *
     * @param Model $model
     * @return Model
     */
    public function process(Model $model)
    {
        $model->{$this->testParameter} = $this->testParameterValue;

        return $model;
    }
}
