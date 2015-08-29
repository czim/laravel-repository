<?php
namespace Czim\Repository\PostProcessors;

use Czim\Repository\Contracts\PostProcessorInterface;
use Illuminate\Database\Eloquent\Model;

class ApplyExtraHiddenAndVisibleAttributes implements PostProcessorInterface
{

    /**
     * List of fields to make sure are hidden on the model
     *
     * @var array
     */
    protected $hidden;

    /**
     * List of fields to make sure are visible on the model
     *
     * @var array
     */
    protected $unhidden;


    /**
     * @param array $hidden
     * @param array $unhidden
     */
    public function __construct(array $hidden, array $unhidden)
    {
        $this->hidden = $hidden;
        $this->unhidden = $unhidden;
    }

    /**
     * Applies processing to a single model
     *
     * @param Model $model
     * @return Model
     */
    public function process(Model $model)
    {
        $hiddenOnModel = $model->getHidden();

        foreach ($this->unhidden as $unhidden) {

            if (($key = array_search($unhidden, $hiddenOnModel)) !== false) {

                unset($hiddenOnModel[ $key ]);
            }
        }

        $hiddenOnModel = array_merge($hiddenOnModel, $this->hidden);

        $model->setHidden($hiddenOnModel);

        return $model;
    }
}
