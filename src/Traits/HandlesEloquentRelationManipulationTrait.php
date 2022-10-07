<?php

declare(strict_types=1);

namespace Czim\Repository\Traits;

use Czim\Repository\Contracts\HandlesEloquentRelationManipulationInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * The point of this is to provide Eloquent relations management through
 * some intermediate object (i.e. a Repository) to make model manipulation
 * easier to test/mock.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @see HandlesEloquentRelationManipulationInterface
 */
trait HandlesEloquentRelationManipulationTrait
{
    /**
     * @param TModel                 $model
     * @param string                 $relation name of the relation (method name)
     * @param array<int, int|string> $ids      list of id's to connect to
     * @param bool                   $detaching
     */
    public function sync(Model $model, string $relation, array $ids, bool $detaching = true): void
    {
        $model->{$relation}()->sync($ids, $detaching);
    }

    /**
     * @param TModel               $model
     * @param string               $relation name of the relation (method name)
     * @param int|string           $id
     * @param array<string, mixed> $attributes
     * @param bool                 $touch
     */
    public function attach(
        Model $model,
        string $relation,
        int|string $id,
        array $attributes = [],
        bool $touch = true,
    ): void {
        $model->{$relation}()->attach($id, $attributes, $touch);
    }

    /**
     * @param TModel                 $model
     * @param string                 $relation name of the relation (method name)
     * @param array<int, int|string> $ids
     * @param bool                   $touch
     */
    public function detach(Model $model, string $relation, array $ids = [], bool $touch = true): void
    {
        $model->{$relation}()->detach($ids, $touch);
    }

    /**
     * @param TModel            $model
     * @param string            $relation name of the relation (method name)
     * @param TModel|int|string $with
     */
    public function associate(Model $model, string $relation, Model|int|string $with): void
    {
        $model->{$relation}()->associate($with);
    }

    /**
     * Excecutes a dissociate on the model model provided.
     *
     * @param TModel  $model
     * @param string $relation name of the relation (method name)
     */
    public function dissociate(Model $model, string $relation): void
    {
        $model->{$relation}()->dissociate();
    }
}
