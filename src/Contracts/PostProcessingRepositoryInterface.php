<?php

namespace Czim\Repository\Contracts;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface PostProcessingRepositoryInterface
{
    /**
     * Returns the default list of postprocessors to apply to models
     * before returning the result to anything using the repository.
     *
     * Each entry is combination of a key (the classname of the postprocessor)
     * and a value (set of parameters, or Closure that generates parameters).
     *
     * The idea is that on each call, the postprocessors are instantiated,
     * and the parameters (if any) set for them, so any updates on the
     * repository are reflected by the processors.
     *
     * @return Collection|PostProcessorInterface[]
     */
    public function defaultPostProcessors();

    /**
     * Restores prostprocessors to default collection
     *
     * @return $this
     */
    public function restoreDefaultPostProcessors();

    /**
     * Pushes a postProcessor to apply to all models retrieved
     *
     * @param string             $class
     * @param array|Closure|null $parameters
     * @return $this
     */
    public function pushPostProcessor($class, $parameters = null);

    /**
     * Removes postProcessor
     *
     * @param string $class
     * @return $this
     */
    public function removePostProcessor($class);

    /**
     * Runs the result for retrieval calls to the repository
     * through postprocessing.
     *
     * @param Collection|Model|null $result the result of the query, ready for postprocessing
     * @return Model|Collection|mixed[]|null
     */
    public function postProcess($result);

    /**
     * Unhide an otherwise hidden attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     *
     * @param  string $attribute name of the attribute to unhide
     * @return $this
     */
    public function unhideAttribute($attribute);

    /**
     * @param array|Arrayable $attributes
     * @return $this
     */
    public function unhideAttributes($attributes);

    /**
     * Hide an otherwise visible attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     *
     * @param  string $attribute name of the attribute to hide
     * @return $this
     */
    public function hideAttribute($attribute);

    /**
     * @param array|Arrayable $attributes
     * @return $this
     */
    public function hideAttributes($attributes);

    /**
     * Resets any hidden or unhidden attribute changes
     */
    public function resetHiddenAttributes();
}
