<?php
namespace Czim\Repository;

use Czim\Repository\Contracts\PostProcessingRepositoryInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Czim\Repository\Contracts\PostProcessorInterface;
use Czim\Repository\PostProcessors\ApplyExtraHiddenAndVisibleAttributes;
use Closure;
use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Class ExtendedPostProcessingRepository
 *
 * Extends the ExtendedRepository with PostProcessing functionality,
 * including convenience methods for hiding/unhiding Model properties.
 */
abstract class ExtendedPostProcessingRepository extends ExtendedRepository implements PostProcessingRepositoryInterface
{
    /**
     * @var array
     */
    protected $extraHidden = [];

    /**
     * @var array
     */
    protected $extraUnhidden = [];

    /**
     * The postprocessors to apply to the returned results for the repository
     * (only all() and find(), and similar calls)
     *
     * @var Collection
     */
    protected $postProcessors;



    /**
     * @param App  $app
     * @param Collection $collection
     */
    public function __construct(App $app, Collection $collection)
    {
        parent::__construct($app, $collection);

        $this->restoreDefaultPostProcessors();
    }


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
     * @return Collection
     */
    public function defaultPostProcessors()
    {
        return new Collection([
            ApplyExtraHiddenAndVisibleAttributes::class => function () {
                return [$this->extraHidden, $this->extraUnhidden];
            },
        ]);
    }


    // -------------------------------------------------------------------------
    //      PostProcessors
    // -------------------------------------------------------------------------

    /**
     * Restores prostprocessors to default collection
     *
     * @return $this
     */
    public function restoreDefaultPostProcessors()
    {
        $this->postProcessors = $this->defaultPostProcessors();

        return $this;
    }

    /**
     * Pushes a postProcessor to apply to all models retrieved
     *
     * @param string        $class
     * @param array|Closure $parameters
     * @return $this
     */
    public function pushPostProcessor($class, $parameters = null)
    {
        $this->postProcessors->put($class, $parameters);

        return $this;
    }

    /**
     * Removes postProcessor
     *
     * @param $class
     * @return $this
     */
    public function removePostProcessor($class)
    {
        $this->postProcessors->forget($class);

        return $this;
    }

    /**
     * Runs the result for retrieval calls to the repository
     * through postprocessing.
     *
     * @param Collection|Model|null $result the result of the query, ready for postprocessing
     * @return Model|Collection|null
     */
    public function postProcess($result)
    {
        // determine whether there is anything to process
        if (    is_null($result)
            ||  is_a($result, Collection::class) && $result->isEmpty()
        ) {
            return $result;
        }

        // check if there is anything to do process it through
        if ($this->postProcessors->isEmpty()) return $result;


        // for each Model, instantiate and apply the processors
        if (is_a($result, Collection::class)) {

            $result->transform(function ($model) {
                return $this->applyPostProcessorsToModel($model);
            });

        } elseif (is_a($result, AbstractPaginator::class)) {
            // result is paginate() result
            // do not apply postprocessing for now (how would we even do it?)
            return $result;

        } else {
            // result is a model
            $result = $this->applyPostProcessorsToModel($result);
        }

        return $result;
    }

    /**
     * Applies the currently active postprocessors to a model
     *
     * @param Model $model
     * @return Model
     */
    protected function applyPostProcessorsToModel(Model $model)
    {
        foreach ($this->postProcessors as $processorClass => $parameters) {

            // if a processor class was added as a value instead of a key, it
            // does not have parameters
            if (is_numeric($processorClass)) {
                $processorClass = $parameters;
                $parameters     = null;
            }

            $processor = $this->makePostProcessor($processorClass, $parameters);

            $model = $processor->process($model);
        }

        return $model;
    }

    /**
     * @param string $processor
     * @param mixed  $parameters flexible parameter input can be string, array or closure that generates either
     * @return PostProcessorInterface
     */
    protected function makePostProcessor($processor, $parameters = null)
    {
        // no parameters? simple make
        if (is_null($parameters)) {

            $parameters = [];

        } elseif (is_callable($parameters)) {

            $parameters = $parameters();
        }

        if ( ! is_array($parameters)) {

            $parameters = [ $parameters ];
        }

        return app($processor, $parameters);
    }


    // -------------------------------------------------------------------------
    //      Attribute hiding
    // -------------------------------------------------------------------------

    /**
     * Unhide an otherwise hidden attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     *
     * @param  string $attribute name of the attribute to unhide
     * @return $this
     */
    public function unhideAttribute($attribute)
    {
        if ( ! in_array($attribute, $this->extraUnhidden)) {

            $this->extraUnhidden[] = $attribute;
        }

        return $this;
    }

    /**
     * @param array|Arrayable $attributes
     * @return $this
     */
    public function unhideAttributes($attributes)
    {
        if ( ! empty($attributes)) {

            foreach ($attributes as $attribute) {

                $this->unhideAttribute($attribute);
            }
        }

        return $this;
    }

    /**
     * Hide an otherwise visible attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     *
     * @param  string $attribute name of the attribute to hide
     * @return $this
     */
    public function hideAttribute($attribute)
    {
        if (($key = array_search($attribute, $this->extraUnhidden)) !== false) {

            unset($this->extraUnhidden[ $key ]);

        } else {

            if ( ! in_array($attribute, $this->extraHidden)) {
                $this->extraHidden[] = $attribute;
            }
        }

        return $this;
    }

    /**
     * @param array|Arrayable $attributes
     * @return $this
     */
    public function hideAttributes($attributes)
    {
        if ( ! empty($attributes)) {

            foreach ($attributes as $attribute) {

                $this->hideAttribute($attribute);
            }
        }

        return $this;
    }

    /**
     * Resets any hidden or unhidden attribute changes
     */
    public function resetHiddenAttributes()
    {
        $this->extraHidden   = [];
        $this->extraUnhidden = [];
    }


    // -------------------------------------------------------------------------
    //      Overrides for applying postprocessing
    // -------------------------------------------------------------------------

    /**
     * Override
     *
     * @param array $columns
     * @return Model|null
     */
    public function first($columns = ['*'])
    {
        return $this->postProcess( parent::first($columns) );
    }

    /**
     * Override
     *
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        return $this->postProcess( parent::firstOrFail($columns) );
    }

    /**
     * Override
     *
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->postProcess( parent::all($columns) );
    }

    /**
     * Override
     *
     * @param int   $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate($perPage = 1, $columns = ['*'])
    {
        return $this->postProcess( parent::paginate($perPage, $columns) );
    }

    /**
     * Override
     *
     * @param       $id
     * @param array $columns
     * @return Model|null
     */
    public function find($id, $columns = ['*'])
    {
        return $this->postProcess( parent::find($id, $columns) );
    }

    /**
     * Override
     *
     * @param       $id
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        return $this->postProcess( parent::findOrFail($id, $columns) );
    }

    /**
     * Override
     *
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return Model|Null
     */
    public function findBy($attribute, $value, $columns = ['*'])
    {
        return $this->postProcess( parent::findBy($attribute, $value, $columns) );
    }
    /**
     * Override
     *
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        return $this->postProcess( parent::findAllBy($attribute, $value, $columns) );
    }
    /**
     * Override
     *
     * @param array $where
     * @param array $columns
     * @param bool  $or
     *
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function findWhere($where, $columns = ['*'], $or = false)
    {
        return $this->postProcess( parent::findWhere($where, $columns, $or) );
    }


    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * @param Closure $callback must return query/builder compatible
     * @param array   $columns
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function allCallback(Closure $callback, $columns = ['*'])
    {
        return $this->postProcess( parent::allCallback($callback, $columns) );
    }

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * @param Closure $callback must return query/builder compatible
     * @param array   $columns
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function findCallback(Closure $callback, $columns = ['*'])
    {
        return $this->postProcess( parent::findCallback($callback, $columns) );
    }

}
