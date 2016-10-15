<?php
namespace Czim\Repository\Contracts;

use Closure;
use Czim\Repository\Exceptions\RepositoryException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{

    /**
     * Returns specified model class name.
     *
     * Note that this is the only abstract method.
     *
     * @return Model
     */
    public function model();

    /**
     * Creates instance of model to start building query for
     *
     * @param bool $storeModel if true, this becomes a fresh $this->model property
     * @return EloquentBuilder
     * @throws RepositoryException
     */
    public function makeModel($storeModel = true);

    /**
     * Give unexecuted query for current criteria
     *
     * @return EloquentBuilder
     */
    public function query();

    /**
     * Does a simple count(*) for the model / scope
     *
     * @return int
     */
    public function count();

    /**
     * Returns first match
     *
     * @param array $columns
     * @return Model|null
     */
    public function first($columns = ['*']);

    /**
     * Returns first match or throws exception if not found
     *
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function firstOrFail($columns = ['*']);

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*']);

    /**
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function pluck($value, $key = null);

    /**
     * @param  string $value
     * @param  string $key
     * @return array
     * @deprecated
     */
    public function lists($value, $key = null);

    /**
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param null $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 1, $columns = ['*'], $pageName = 'page', $page = null);

    /**
     * @param       $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*']);

    /**
     * Returns first match or throws exception if not found
     *
     * @param int   $id
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*']);

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = ['*']);

    /**
     * @param       $attribute
     * @param       $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*']);

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param array $where
     * @param array $columns
     * @param bool  $or
     *
     * @return Collection|null
     */
    public function findWhere($where, $columns = ['*'], $or = false);

    /**
     * Creates a model and returns it
     *
     * @param array $data
     * @return Model|null
     */
    public function create(array $data);

    /**
     * Updates a model by $id
     *
     * @param array  $data
     * @param        $id
     * @param string $attribute
     * @return bool     false if could not find model or not succesful in updating
     */
    public function update(array $data, $id, $attribute = 'id');

    /**
     * Deletes a model by $id
     *
     * @param $id
     * @return boolean
     */
    public function delete($id);

    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * @param Closure $callback must return query/builder compatible
     * @param array   $columns
     * @return Collection
     * @throws \Exception
     */
    public function allCallback(Closure $callback, $columns = ['*']);

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * @param Closure $callback must return query/builder compatible
     * @param array   $columns
     * @return Collection
     * @throws \Exception
     */
    public function findCallback(Closure $callback, $columns = ['*']);


    /**
     * Returns a collection with the default criteria for the repository.
     * These should be the criteria that apply for (almost) all calls
     *
     * Default set of criteria to apply to this repository
     * Note that this also needs all the parameters to send to the constructor
     * of each (and this CANNOT be solved by using the classname of as key,
     * since the same Criteria may be applied more than once).
     *
     * @return Collection;
     */
    public function defaultCriteria();

    /**
     * Builds the default criteria and replaces the criteria stack to apply with
     * the default collection.
     *
     * @return $this
     */
    public function restoreDefaultCriteria();

    /**
     * Sets criteria to empty collection
     *
     * @return $this
     */
    public function clearCriteria();

    /**
     * Sets or unsets ignoreCriteria flag. If it is set, all criteria (even
     * those set to apply once!) will be ignored.
     *
     * @param bool $ignore
     * @return $this
     */
    public function ignoreCriteria($ignore = true);

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     *
     * @return Collection
     */
    public function getCriteria();

    /**
     * Applies Criteria to the model for the upcoming query
     *
     * This takes the default/standard Criteria, then overrides
     * them with whatever is found in the onceCriteria list
     *
     * @return $this
     */
    public function applyCriteria();

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
    public function pushCriteria(CriteriaInterface $criteria, $key = null);

    /**
     * Removes criteria by key, if it exists
     *
     * @param string $key
     * @return $this
     */
    public function removeCriteria($key);

    /**
     * Pushes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * @param CriteriaInterface $criteria
     * @param string            $key
     * @return $this
     */
    public function pushCriteriaOnce(CriteriaInterface $criteria, $key = null);

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
    public function removeCriteriaOnce($key);

}
