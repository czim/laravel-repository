<?php

namespace Czim\Repository\Contracts;

use Closure;
use Czim\Repository\Exceptions\RepositoryException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface BaseRepositoryInterface
{
    /**
     * Returns specified model class name.
     *
     * @return class-string<TModel>
     */
    public function model(): string;


    /**
     * Creates instance of model to start building query for
     *
     * @param bool $storeModel if true, this becomes a fresh $this->model property
     * @return TModel
     * @throws RepositoryException
     */
    public function makeModel(bool $storeModel = true): Model;

    /**
     * Give unexecuted (fresh) query wioth the current applied criteria.
     *
     * @return EloquentBuilder<TModel>|BaseBuilder
     * @throws RepositoryException
     */
    public function query(): EloquentBuilder|BaseBuilder;

    public function count(): int;

    /**
     * @param string[] $columns
     * @return TModel|null
     */
    public function first(array $columns = ['*']): ?Model;

    /**
     * @param string[] $columns
     * @return TModel|null
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $columns = ['*']): ?Model;

    /**
     * @param string[] $columns
     * @return EloquentCollection<int, TModel>
     */
    public function all(array $columns = ['*']): EloquentCollection;

    /**
     * @param string      $value
     * @param string|null $key
     * @return Collection<int|string, mixed>
     * @throws RepositoryException
     */
    public function pluck(string $value, ?string $key = null): Collection;

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
    ): LengthAwarePaginator;

    /**
     * @param int|string  $id
     * @param string[]    $columns
     * @param string|null $attribute
     * @return TModel|null
     */
    public function find(int|string $id, array $columns = ['*'], ?string $attribute = null): ?Model;

    /**
     * @param int|string $id
     * @param string[]   $columns
     * @return TModel
     * @throws ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /**
     * @param string   $attribute
     * @param mixed    $value
     * @param string[] $columns
     * @return TModel|null
     */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): ?Model;

    /**
     * @param string   $attribute
     * @param mixed    $value
     * @param string[] $columns
     * @return EloquentCollection<int, TModel>
     */
    public function findAllBy(string $attribute, mixed $value, $columns = ['*']): EloquentCollection;

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param array<string, callable|array<int, string>|mixed> $where
     * @param string[]                                         $columns
     * @param bool                                             $or
     * @return EloquentCollection<int, TModel>
     */
    public function findWhere(array $where, array $columns = ['*'], bool $or = false): EloquentCollection;

    /**
     * Makes a new model without persisting it.
     *
     * @param array<string, mixed> $data
     * @return TModel
     * @throws MassAssignmentException|RepositoryException
     */
    public function make(array $data): Model;

    /**
     * Creates a model and returns it
     *
     * @param array<string, mixed> $data
     * @return TModel|null
     * @throws RepositoryException
     */
    public function create(array $data): ?Model;

    /**
     * @param array<string, mixed> $data
     * @param int|string           $id
     * @param string|null          $attribute
     * @return bool
     */
    public function update(array $data, int|string $id, ?string $attribute = null): bool;

    /**
     * Finds and fills a model by id, without persisting changes.
     *
     * @param array<string, mixed> $data
     * @param int|string           $id
     * @param string|null          $attribute
     * @return TModel|false
     * @throws MassAssignmentException|ModelNotFoundException
     */
    public function fill(array $data, int|string $id, ?string $attribute = null): Model|false;

    /**
     * Deletes a model by id.
     *
     * @param int|string $id
     * @return int
     * @throws RepositoryException
     */
    public function delete(int|string $id): int;

    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * @param Closure  $callback must return query/builder compatible
     * @param string[] $columns
     * @return EloquentCollection<int, TModel>
     * @throws RepositoryException
     */
    public function allCallback(Closure $callback, array $columns = ['*']): EloquentCollection;

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * @param Closure  $callback must return query/builder compatible
     * @param string[] $columns
     * @return TModel|null
     * @throws RepositoryException
     */
    public function findCallback(Closure $callback, array $columns = ['*']): ?Model;

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
     * @return Collection<int|string, CriteriaInterface<TModel, Model>>
     */
    public function defaultCriteria(): Collection;

    /**
     * Builds the default criteria and replaces the criteria stack to apply with
     * the default collection.
     */
    public function restoreDefaultCriteria(): void;

    public function clearCriteria(): void;

    /**
     * Sets or unsets ignoreCriteria flag. If it is set, all criteria (even
     * those set to apply once!) will be ignored.
     *
     * @param bool $ignore
     */
    public function ignoreCriteria(bool $ignore = true): void;

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     *
     * @return Collection<int|string, CriteriaInterface<TModel, Model>>
     */
    public function getCriteria(): Collection;

    /**
     * Returns a cloned set of all currently set once criteria.
     *
     * @return Collection<int|string, CriteriaInterface<TModel, Model>>
     */
    public function getOnceCriteria(): Collection;

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     *
     * @return Collection<int|string, CriteriaInterface<TModel, Model>>
     */
    public function getAllCriteria(): Collection;

    /**
     * Applies Criteria to the model for the upcoming query
     *
     * This takes the default/standard Criteria, then overrides
     * them with whatever is found in the onceCriteria list
     *
     * @throws RepositoryException
     */
    public function applyCriteria(): void;

    /**
     * Pushes Criteria, optionally by identifying key.
     *
     * If a criteria already exists for the key, it is overridden
     * Note that this does NOT overrule any onceCriteria, even if set by key!
     *
     * @param CriteriaInterface<TModel, Model> $criteria
     * @param string|null                      $key      Unique identifier, may be used to remove and overwrite criteria
     */
    public function pushCriteria(CriteriaInterface $criteria, ?string $key = null): void;

    /**
     * @param string $key
     */
    public function removeCriteria(string $key): void;

    /**
     * Pushes Criteria, but only for the next call, resets to default afterwards.
     *
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * @param CriteriaInterface<TModel, Model> $criteria
     * @param string|null                      $key
     * @return $this
     */
    public function pushCriteriaOnce(CriteriaInterface $criteria, ?string $key = null): static;

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
    public function removeCriteriaOnce(string $key): static;
}
