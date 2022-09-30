<?php

declare(strict_types=1);

namespace Czim\Repository;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Contracts\CriteriaInterface;
use Czim\Repository\Criteria\NullCriteria;
use Czim\Repository\Exceptions\RepositoryException;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Basic repository for retrieving and manipulating Eloquent models.
 *
 * One of the main differences with Bosnadev's repository is that With this,
 * criteria may be given a key identifier, by which they may later be removed
 * or overriden. This way you can, for instance, set a default criterion for
 * ordering by a certain column, but in other cases, without reinstantiating, order
 * by other columns, by marking the Criteria that does the ordering with key 'order'.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected ContainerInterface $app;

    /**
     * @var TModel|Model|EloquentBuilder|BaseBuilder
     */
    protected Model|EloquentBuilder|BaseBuilder $modelOrQuery;

    /**
     * Criteria to keep and use for all coming queries
     *
     * @var Collection<int|string, CriteriaInterface>
     */
    protected Collection $criteria;

    /**
     * The Criteria to only apply to the next query
     *
     * @var Collection<int|string, CriteriaInterface>
     */
    protected Collection $onceCriteria;

    /**
     * List of criteria that are currently active (updates when criteria are stripped)
     * So this is a dynamic list that can change during calls of various repository
     * methods that alter the active criteria.
     *
     * @var Collection<int|string, CriteriaInterface>
     */
    protected Collection $activeCriteria;

    /**
     * Whether to skip ALL criteria.
     *
     * @var bool
     */
    protected bool $ignoreCriteria = false;

    /**
     * Default number of paginated items
     *
     * @var int
     */
    protected int $perPage = 1;


    /**
     * @param ContainerInterface                        $container
     * @param Collection<int|string, CriteriaInterface> $initialCriteria
     * @throws RepositoryException
     */
    public function __construct(ContainerInterface $container, Collection $initialCriteria)
    {
        if ($initialCriteria->isEmpty()) {
            $initialCriteria = $this->defaultCriteria();
        }

        $this->app            = $container;
        $this->criteria       = $initialCriteria;
        $this->onceCriteria   = new Collection();
        $this->activeCriteria = new Collection();

        $this->makeModel();
    }


    /**
     * Returns specified model class name.
     *
     * @return class-string<TModel>
     */
    public abstract function model(): string;


    /**
     * Creates instance of model to start building query for
     *
     * @param bool $storeModel if true, this becomes a fresh $this->model property
     * @return TModel&Model
     * @throws RepositoryException
     */
    public function makeModel(bool $storeModel = true): Model
    {
        try {
            $model = $this->app->get($this->model());
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            throw new RepositoryException(
                "Class {$this->model()} could not be instantiated through the container",
                $exception->getCode(),
                $exception
            );
        }

        if (! $model instanceof Model) {
            throw new RepositoryException(
                "Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model"
            );
        }

        if ($storeModel) {
            $this->modelOrQuery = $model;
        }

        return $model;
    }


    // -------------------------------------------------------------------------
    //      Retrieval methods
    // -------------------------------------------------------------------------

    /**
     * Give unexecuted (fresh) query wioth the current applied criteria.
     *
     * @return EloquentBuilder|BaseBuilder
     * @throws RepositoryException
     */
    public function query(): EloquentBuilder|BaseBuilder
    {
        $this->applyCriteria();

        if ($this->modelOrQuery instanceof Model) {
            return $this->modelOrQuery->newQuery();
        }

        return clone $this->modelOrQuery;
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * @param string[] $columns
     * @return TModel|null
     */
    public function first(array $columns = ['*']): ?Model
    {
        return $this->query()->first($columns);
    }

    /**
     * @param string[] $columns
     * @return TModel|null
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $columns = ['*']): ?Model
    {
        $result = $this->query()->first($columns);

        if (! empty($result)) {
            return $result;
        }

        throw (new ModelNotFoundException())->setModel($this->model());
    }

    /**
     * @param string[] $columns
     * @return EloquentCollection<int, TModel>
     */
    public function all(array $columns = ['*']): EloquentCollection
    {
        return $this->query()->get($columns);
    }

    /**
     * @param string      $value
     * @param string|null $key
     * @return Collection<int|string, mixed>
     * @throws RepositoryException
     */
    public function pluck(string $value, ?string $key = null): Collection
    {
        $this->applyCriteria();

        return $this->query()->pluck($value, $key);
    }

    /**
     * @param int|null $perPage
     * @param string[] $columns
     * @param string   $pageName
     * @param int|null $page
     * @return LengthAwarePaginator&iterable<int, TModel>
     */
    public function paginate(
        ?int $perPage = null,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null,
    ): LengthAwarePaginator {
        $perPage ??= $this->getDefaultPerPage();

        return $this->query()
            ->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * @param int|string  $id
     * @param string[]    $columns
     * @param string|null $attribute
     * @return TModel|null
     */
    public function find(int|string $id, array $columns = ['*'], ?string $attribute = null): ?Model
    {
        $query = $this->query();

        if ($attribute !== null && $attribute !== $query->getModel()->getKeyName()) {
            return $query->where($attribute, $id)->first($columns);
        }

        return $query->find($id, $columns);
    }

    /**
     * @param int|string $id
     * @param string[]   $columns
     * @return TModel
     * @throws ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        $result = $this->query()->find($id, $columns);

        if (! empty($result)) {
            return $result;
        }

        throw (new ModelNotFoundException())->setModel($this->model(), $id);
    }

    /**
     * @param string   $attribute
     * @param mixed    $value
     * @param string[] $columns
     * @return TModel|null
     */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->query()
            ->where($attribute, $value)
            ->first($columns);
    }

    /**
     * @param string   $attribute
     * @param mixed    $value
     * @param string[] $columns
     * @return EloquentCollection<int, TModel>
     */
    public function findAllBy(string $attribute, mixed $value, $columns = ['*']): EloquentCollection
    {
        return $this->query()
            ->where($attribute, $value)
            ->get($columns);
    }

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param array<string, callable|array<int, string>|mixed> $where
     * @param string[]                                         $columns
     * @param bool                                             $or
     * @return EloquentCollection
     */
    public function findWhere(array $where, array $columns = ['*'], bool $or = false): EloquentCollection
    {
        $model = $this->query();

        foreach ($where as $field => $value) {
            if ($value instanceof Closure) {
                $model = (! $or)
                    ? $model->where($value)
                    : $model->orWhere($value);

            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    [$field, $operator, $search] = $value;

                    $model = (! $or)
                        ? $model->where($field, $operator, $search)
                        : $model->orWhere($field, $operator, $search);
                } elseif (count($value) === 2) {
                    [$field, $search] = $value;

                    $model = (! $or)
                        ? $model->where($field, $search)
                        : $model->orWhere($field, $search);
                }
            } else {
                $model = (! $or)
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
     * Makes a new model without persisting it.
     *
     * @param array<string, mixed> $data
     * @return TModel
     * @throws MassAssignmentException|RepositoryException
     */
    public function make(array $data): Model
    {
        return $this->makeModel(false)->fill($data);
    }

    /**
     * Creates a model and returns it
     *
     * @param array<string, mixed> $data
     * @return TModel|null
     * @throws RepositoryException
     */
    public function create(array $data): ?Model
    {
        return $this->makeModel(false)->create($data);
    }

    /**
     * @param array<string, mixed> $data
     * @param int|string           $id
     * @param string|null          $attribute
     * @return bool
     */
    public function update(array $data, int|string $id, ?string $attribute = null): bool
    {
        $model = $this->find($id, ['*'], $attribute);

        if (! $model) {
            return false;
        }

        return $model->update($data);
    }

    /**
     * Finds and fills a model by id, without persisting changes.
     *
     * @param array<string, mixed> $data
     * @param int|string           $id
     * @param string|null          $attribute
     * @return Model|false
     * @throws MassAssignmentException|ModelNotFoundException
     */
    public function fill(array $data, int|string $id, ?string $attribute = null): Model|false
    {
        $model = $this->find($id, ['*'], $attribute);

        if (! $model) {
            throw (new ModelNotFoundException())->setModel($this->model());
        }

        return $model->fill($data);
    }

    /**
     * Deletes a model by id.
     *
     * @param int|string $id
     * @return int
     * @throws RepositoryException
     */
    public function delete(int|string $id): int
    {
        return $this->makeModel(false)->destroy($id);
    }


    // -------------------------------------------------------------------------
    //      With custom callback
    // -------------------------------------------------------------------------

    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * @param Closure  $callback must return query/builder compatible
     * @param string[] $columns
     * @return EloquentCollection<int, TModel>
     * @throws RepositoryException
     */
    public function allCallback(Closure $callback, array $columns = ['*']): EloquentCollection
    {
        $result = $callback($this->query());

        $this->assertValidCustomCallback($result);

        /** @var EloquentBuilder|BaseBuilder $result */
        return $result->get($columns);
    }

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * @param Closure  $callback must return query/builder compatible
     * @param string[] $columns
     * @return TModel|null
     * @throws RepositoryException
     */
    public function findCallback(Closure $callback, array $columns = ['*']): ?Model
    {
        $result = $callback($this->query());

        $this->assertValidCustomCallback($result);

        /** @var EloquentBuilder|BaseBuilder $result */
        return $result->first($columns);
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
     * @return Collection<int|string, CriteriaInterface>
     */
    public function defaultCriteria(): Collection
    {
        return new Collection();
    }


    /**
     * Builds the default criteria and replaces the criteria stack to apply with the default collection.
     */
    public function restoreDefaultCriteria(): void
    {
        $this->criteria = $this->defaultCriteria();
    }

    public function clearCriteria(): void
    {
        $this->criteria = new Collection();
    }

    /**
     * Sets or unsets ignoreCriteria flag. If it is set, all criteria (even
     * those set to apply once!) will be ignored.
     *
     * @param bool $ignore
     */
    public function ignoreCriteria(bool $ignore = true): void
    {
        $this->ignoreCriteria = $ignore;
    }

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     *
     * @return Collection<int|string, CriteriaInterface>
     */
    public function getCriteria(): Collection
    {
        return clone $this->criteria;
    }

    /**
     * Returns a cloned set of all currently set once criteria.
     *
     * @return Collection<int|string, CriteriaInterface>
     */
    public function getOnceCriteria(): Collection
    {
        return clone $this->onceCriteria;
    }

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     *
     * @return Collection<int|string, CriteriaInterface>
     */
    public function getAllCriteria(): Collection
    {
        return $this->getCriteria()
            ->merge($this->getOnceCriteria());
    }

    /**
     * Applies Criteria to the model for the upcoming query.
     *
     * This takes the default/standard Criteria, then overrides
     * them with whatever is found in the onceCriteria list
     *
     * @throws RepositoryException
     */
    public function applyCriteria(): void
    {
        // If we're ignoring criteria, the model must be remade without criteria ...
        if ($this->ignoreCriteria === true) {
            // ... and make sure that they are re-applied when we stop ignoring.
            if (! $this->activeCriteria->isEmpty()) {
                $this->makeModel();
                $this->activeCriteria = new Collection();
            }
            return;
        }

        if ($this->areActiveCriteriaUnchanged()) {
            return;
        }

        // If the new Criteria are different, clear the model and apply the new Criteria.
        $this->makeModel();

        $this->markAppliedCriteriaAsActive();


        // Apply the collected criteria to the query.
        foreach ($this->getCriteriaToApply() as $criteria) {
            $this->modelOrQuery = $criteria->apply($this->modelOrQuery, $this);
        }

        $this->clearOnceCriteria();
    }

    /**
     * Pushes Criteria, optionally by identifying key.
     *
     * If a criteria already exists for the key, it is overridden
     * Note that this does NOT overrule any onceCriteria, even if set by key!
     *
     * @param CriteriaInterface $criteria
     * @param string|null       $key        Unique identifier, may be used to remove and overwrite criteria
     */
    public function pushCriteria(CriteriaInterface $criteria, ?string $key = null): void
    {
        // Standard bosnadev behavior.
        if ($key === null) {
            $this->criteria->push($criteria);
            return;
        }

        // Set/override by key.
        $this->criteria->put($key, $criteria);
    }

    public function removeCriteria(string $key): void
    {
        $this->criteria->forget($key);
    }

    /**
     * Pushes Criteria, but only for the next call, resets to default afterwards.
     *
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * @param CriteriaInterface $criteria
     * @param string|null       $key
     * @return $this
     */
    public function pushCriteriaOnce(CriteriaInterface $criteria, ?string $key = null): static
    {
        if ($key === null) {
            $this->onceCriteria->push($criteria);
            return $this;
        }

        // Set/override by key.
        $this->onceCriteria->put($key, $criteria);
        return $this;
    }

    /**
     * Removes Criteria, but only for the next call, resets to default afterwards.
     *
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * In effect, this adds a NullCriteria to onceCriteria by key, disabling any criteria
     * by that key in the normal criteria list.
     *
     * @param string $key
     * @return $this
     */
    public function removeCriteriaOnce(string $key): static
    {
        // If not present in normal list, there is nothing to override.
        if (! $this->criteria->has($key)) {
            return $this;
        }

        // Override by key with null-value.
        $this->onceCriteria->put($key, new NullCriteria());
        return $this;
    }


    /**
     * Returns the criteria that must be applied for the next query.
     *
     * @return Collection<int|string, CriteriaInterface>
     */
    protected function getCriteriaToApply(): Collection
    {
        // get the standard criteria
        $criteriaToApply = $this->getCriteria();

        // overrule them with criteria to be applied once
        if (! $this->onceCriteria->isEmpty()) {
            foreach ($this->onceCriteria as $onceKey => $onceCriteria) {
                // If there is no key, we can only add the criteria.
                if (is_numeric($onceKey)) {
                    $criteriaToApply->push($onceCriteria);
                    continue;
                }

                // If there is a key, override or remove; if Null, remove criterion.
                if ($onceCriteria instanceof NullCriteria) {
                    $criteriaToApply->forget($onceKey);
                    continue;
                }

                // Otherwise, overide the criteria.
                $criteriaToApply->put($onceKey, $onceCriteria);
            }
        }

        return $criteriaToApply;
    }

    /**
     * Checks whether the criteria that are currently pushed are the same as the ones that were previously applied.
     *
     * @return bool
     */
    protected function areActiveCriteriaUnchanged(): bool
    {
        return ($this->onceCriteria->isEmpty()
            && $this->criteria == $this->activeCriteria
        );
    }

    /**
     * Marks the active criteria, so we can later check what is currently active.
     */
    protected function markAppliedCriteriaAsActive(): void
    {
        $this->activeCriteria = $this->getCriteriaToApply();
    }

    /**
     * After applying, removes the criteria that should only have applied once
     */
    protected function clearOnceCriteria(): void
    {
        if ($this->onceCriteria->isEmpty()) {
            return;
        }

        $this->onceCriteria = new Collection();
    }

    protected function assertValidCustomCallback(mixed $result): void
    {
        if (
            ! $result instanceof Model
            && ! $result instanceof EloquentBuilder
            && ! $result instanceof BaseBuilder
        ) {
            throw new InvalidArgumentException(
                'Incorrect allCustom call in repository. '
                . 'The callback must return a QueryBuilder/EloquentBuilder or Model object.'
            );
        }
    }

    /**
     * Returns default per page count.
     *
     * @return int
     */
    protected function getDefaultPerPage(): int
    {
        try {
            $perPage = $this->perPage ?: $this->makeModel(false)->getPerPage();
        } catch (RepositoryException) {
            $perPage = 50;
        }

        return config('repository.perPage', $perPage);
    }
}
