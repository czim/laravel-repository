<?php
namespace Czim\Repository;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Contracts\CriteriaInterface;
use Czim\Repository\Criteria\NullCriteria;
use Czim\Repository\Exceptions\RepositoryException;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use InvalidArgumentException;

/**
 * Class BaseRepository
 *
 * Basic repository for retrieving and manipulating Eloquent models.
 *
 * One of the main differences with Bosnadev's repository is that With this,
 * criteria may be given a key identifier, by which they may later be removed
 * or overriden. This way you can, for instance, set a default criterion for
 * ordering by a certain column, but in other cases, without reinstantiating, order
 * by other columns, by marking the Criteria that does the ordering with key 'order'.
 *
 * implements Contracts\RepositoryInterface, Contracts\RepositoryCriteriaInterface
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var Model|EloquentBuilder
     */
    protected $model;

    /**
     * Criteria to keep and use for all coming queries
     *
     * @var Collection
     */
    protected $criteria;

    /**
     * The Criteria to only apply to the next query
     *
     * @var Collection
     */
    protected $onceCriteria;

    /**
     * List of criteria that are currently active (updates when criteria are stripped)
     * So this is a dynamic list that can change during calls of various repository
     * methods that alter the active criteria.
     *
     * @var array
     */
    protected $activeCriteria = null;

    /**
     * Whether to skip ALL criteria
     *
     * @var bool
     */
    protected $ignoreCriteria = false;


    /**
     * @param App        $app
     * @param Collection $collection
     * @throws RepositoryException
     */
    public function __construct(App $app, Collection $collection)
    {
        $this->app            = $app;
        $this->criteria       = $collection;
        $this->onceCriteria   = new Collection();
        $this->activeCriteria = new Collection();

        $this->makeModel();
    }


    /**
     * Returns specified model class name.
     *
     * Note that this is the only abstract method.
     *
     * @return string
     */
    public abstract function model();


    /**
     * Creates instance of model to start building query for
     *
     * @param bool $storeModel  if true, this becomes a fresh $this->model property
     * @return EloquentBuilder
     * @throws RepositoryException
     */
    public function makeModel($storeModel = true)
    {
        $model = $this->app->make($this->model());

        if ( ! $model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        if ($storeModel) $this->model = $model;

        return $model;
    }


    // -------------------------------------------------------------------------
    //      Retrieval methods
    // -------------------------------------------------------------------------

    /**
     * Give unexecuted query for current criteria
     *
     * @return EloquentBuilder
     */
    public function query()
    {
        $this->applyCriteria();

        return clone $this->model;
    }

    /**
     * Does a simple count(*) for the model / scope
     *
     * @return int
     */
    public function count()
    {
        return $this->query()->count();
    }

    /**
     * Returns first match
     *
     * @param array $columns
     * @return Model|null
     */
    public function first($columns = ['*'])
    {
        return $this->query()->first($columns);
    }

    /**
     * Returns first match or throws exception if not found
     *
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        $result = $this->query()->first($columns);

        if ( ! empty($result)) return $result;

        throw (new ModelNotFoundException)->setModel($this->model());
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->query()->get($columns);
    }

    /**
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        $this->applyCriteria();

        $lists = $this->model->lists($value, $key);

        if (is_array($lists)) return $lists;

        return $lists->all();
    }

    /**
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param null $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 1, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->query()
                    ->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * @param       $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        return $this->query()->find($id, $columns);
    }

    /**
     * Returns first match or throws exception if not found
     *
     * @param int   $id
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $result = $this->query()->find($id, $columns);

        if ( ! empty($result)) return $result;

        throw (new ModelNotFoundException)->setModel($this->model());
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = ['*'])
    {
        return $this->query()
                    ->where($attribute, $value)
                    ->first($columns);
    }

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        return $this->query()
                    ->where($attribute, $value)
                    ->get($columns);
    }

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param array|Arrayable $where
     * @param array $columns
     * @param bool  $or
     *
     * @return Collection|null
     */
    public function findWhere($where, $columns = ['*'], $or = false)
    {
        $model = $this->query();

        foreach ($where as $field => $value) {

            if ($value instanceof Closure) {

                $model = ( ! $or)
                    ? $model->where($value)
                    : $model->orWhere($value);

            } elseif (is_array($value)) {

                if (count($value) === 3) {

                    list($field, $operator, $search) = $value;

                    $model = ( ! $or)
                        ? $model->where($field, $operator, $search)
                        : $model->orWhere($field, $operator, $search);

                } elseif (count($value) === 2) {

                    list($field, $search) = $value;

                    $model = ( ! $or)
                        ? $model->where($field, $search)
                        : $model->orWhere($field, $search);
                }

            } else {
                $model = ( ! $or)
                    ? $model->where($field, $value)
                    : $model->orWhere($field, $value);
            }
        }

        return $model->get($columns);
    }


    // -------------------------------------------------------------------------
    //      Manipulation methods
    // -------------------------------------------------------------------------

    /**
     * Creates a model and returns it
     *
     * @param array $data
     * @return Model|null
     */
    public function create(array $data)
    {
        $model = $this->makeModel(false);

        return $model->create($data);
    }

    /**
     * Updates a model by id
     *
     * @param array  $data
     * @param        $id
     * @param string $attribute
     * @return bool     false if could not find model or not succesful in updating
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $model = $this->makeModel(false)->find($id);

        if (empty($model)) return false;

        return $model->fill($data)->save();
    }

    /**
     * Deletes a model by id
     *
     * @param $id
     * @return boolean
     */
    public function delete($id)
    {
        $model = $this->makeModel(false);

        return $model->destroy($id);
    }


    // -------------------------------------------------------------------------
    //      With custom callback
    // -------------------------------------------------------------------------

    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * @param Closure $callback must return query/builder compatible
     * @param array   $columns
     * @return Collection
     * @throws \Exception
     */
    public function allCallback(Closure $callback, $columns = ['*'])
    {
        /** @var EloquentBuilder $result */
        $result = $callback( $this->query() );

        $this->checkValidCustomCallback($result);

        return $result->get($columns);
    }

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * @param Closure $callback must return query/builder compatible
     * @param array   $columns
     * @return Collection
     * @throws \Exception
     */
    public function findCallback(Closure $callback, $columns = ['*'])
    {
        /** @var EloquentBuilder $result */
        $result = $callback( $this->query() );

        $this->checkValidCustomCallback($result);

        return $result->first($columns);
    }

    /**
     * @param $result
     * @throws InvalidArgumentException
     */
    protected function checkValidCustomCallback($result)
    {
        if (    ! is_a($result, Model::class)
            &&  ! is_a($result, EloquentBuilder::class)
            &&  ! is_a($result, DatabaseBuilder::class)
        ) {
            throw new InvalidArgumentException('Incorrect allCustom call in repository. The callback must return a QueryBuilder/EloquentBuilder or Model object.');
        }
    }


    // -------------------------------------------------------------------------
    //      Criteria
    // -------------------------------------------------------------------------

    /**
     * Returns a collection with the default criteria for the repository.
     * These should be the criteria that apply for (almost) all calls
     *
     * Default set of criteria to apply to this repository
     * Note that this also needs all the parameters to send to the constructor
     * of each (and this CANNOT be solved by using the classname of as key,
     * since the same Criteria may be applied more than once).
     *
     * Override with your own defaults (check ExtendedRepository's refreshed,
     * named Criteria for examples).
     *
     * @return Collection;
     */
    public function defaultCriteria()
    {
        return new Collection();
    }


    /**
     * Builds the default criteria and replaces the criteria stack to apply with
     * the default collection.
     *
     * @return $this
     */
    public function restoreDefaultCriteria()
    {
        $this->criteria = $this->defaultCriteria();

        return $this;
    }


    /**
     * Sets criteria to empty collection
     *
     * @return $this
     */
    public function clearCriteria()
    {
        $this->criteria = new Collection();

        return $this;
    }

    /**
     * Sets or unsets ignoreCriteria flag. If it is set, all criteria (even
     * those set to apply once!) will be ignored.
     *
     * @param bool $ignore
     * @return $this
     */
    public function ignoreCriteria($ignore = true)
    {
        $this->ignoreCriteria = $ignore;

        return $this;
    }

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     *
     * @return Collection
     */
    public function getCriteria()
    {
        return clone $this->criteria;
    }

    /**
     * Returns the criteria that must be applied for the next query
     *
     * @return Collection
     */
    protected function getCriteriaToApply()
    {
        // get the standard criteria
        $criteriaToApply = $this->getCriteria();

        // overrule them with criteria to be applied once
        if ( ! $this->onceCriteria->isEmpty()) {

            foreach ($this->onceCriteria as $onceKey => $onceCriteria) {

                // if there is no key, we can only add the criteria
                if (is_numeric($onceKey)) {

                    $criteriaToApply->push($onceCriteria);
                    continue;
                }

                // if there is a key, override or remove
                // if Null, remove criteria
                if (empty($onceCriteria) || is_a($onceCriteria, NullCriteria::class)) {

                    $criteriaToApply->forget($onceKey);
                    continue;
                }

                // otherwise, overide the criteria
                $criteriaToApply->put($onceKey, $onceCriteria);
            }
        }

        return $criteriaToApply;
    }

    /**
     * Applies Criteria to the model for the upcoming query
     *
     * This takes the default/standard Criteria, then overrides
     * them with whatever is found in the onceCriteria list
     *
     * @return $this
     */
    public function applyCriteria()
    {
        // if we're ignoring criteria, the model must be remade without criteria
        if ($this->ignoreCriteria === true) {

            // and make sure that they are re-applied when we stop ignoring
            if ( ! $this->activeCriteria->isEmpty()) {
                $this->makeModel();
                $this->activeCriteria = new Collection();
            }
            return $this;
        }

        if ($this->areActiveCriteriaUnchanged()) return $this;

        // if the new Criteria are different, clear the model and apply the new Criteria
        $this->makeModel();

        $this->markAppliedCriteriaAsActive();


        // apply the collected criteria to the query
        foreach ($this->getCriteriaToApply() as $criteria) {

            if ($criteria instanceof CriteriaInterface) {

                $this->model = $criteria->apply($this->model, $this);
            }
        }

        $this->clearOnceCriteria();

        return $this;
    }

    /**
     * Checks whether the criteria that are currently pushed
     * are the same as the ones that were previously applied
     *
     * @return mixed
     */
    protected function areActiveCriteriaUnchanged()
    {
        return (    $this->onceCriteria->isEmpty()
                &&  $this->criteria == $this->activeCriteria
                );
    }

    /**
     * Marks the active criteria so we can later check what
     * is currently active
     */
    protected function markAppliedCriteriaAsActive()
    {
        $this->activeCriteria = $this->getCriteriaToApply();
    }

    /**
     * After applying, removes the criteria that should only have applied once
     */
    protected function clearOnceCriteria()
    {
        if ( ! $this->onceCriteria->isEmpty()) $this->onceCriteria = new Collection();
    }

    /**
     * Pushes Criteria, optionally by identifying key
     * If a criteria already exists for the key, it is overridden
     *
     * Note that this does NOT overrule any onceCriteria, even if set by key!
     *
     * @param CriteriaInterface $criteria
     * @param string            $key          unique identifier to store criteria as
     *                                        this may be used to remove and overwrite criteria
     *                                        empty for normal automatic numeric key
     * @return $this
     */
    public function pushCriteria(CriteriaInterface $criteria, $key = null)
    {
        // standard bosnadev behavior
        if (is_null($key)) {

            $this->criteria->push($criteria);
            return $this;
        }

        // set/override by key
        $this->criteria->put($key, $criteria);

        return $this;
    }

    /**
     * Removes criteria by key, if it exists
     *
     * @param string $key
     * @return $this
     */
    public function removeCriteria($key)
    {
        $this->criteria->forget($key);

        return $this;
    }

    /**
     * Pushes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * @param CriteriaInterface $criteria
     * @param string            $key
     * @return $this
     */
    public function pushCriteriaOnce(CriteriaInterface $criteria, $key = null)
    {
        if (is_null($key)) {

            $this->onceCriteria->push($criteria);
            return $this;
        }

        // set/override by key
        $this->onceCriteria->put($key, $criteria);

        return $this;
    }


    /**
     * Removes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * In effect, this adds a NullCriteria to onceCriteria by key, disabling any criteria
     * by that key in the normal criteria list.
     *
     * @param string $key
     * @return $this
     */
    public function removeCriteriaOnce($key)
    {
        // if not present in normal list, there is nothing to override
        if ( ! $this->criteria->has($key)) return $this;

        // override by key with Null-value
        $this->onceCriteria->put($key, new NullCriteria);

        return $this;
    }
}
