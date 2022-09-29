<?php

namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HandlesEloquentRelationManipulationInterface
{
    /**
     * @param Model                  $model
     * @param string                 $relation name of the relation (method name)
     * @param array<int, int|string> $ids      list of id's to connect to
     * @param bool                   $detaching
     */
    public function sync(Model $model, string $relation, array $ids, bool $detaching = true): void;

    /**
     * @param Model                $model
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
        bool $touch = true
    ): void;

    /**
     * @param Model                  $model
     * @param string                 $relation name of the relation (method name)
     * @param array<int, int|string> $ids
     * @param bool                   $touch
     */
    public function detach(Model $model, string $relation, array $ids = [], bool $touch = true): void;

    /**
     * @param Model            $model
     * @param string           $relation name of the relation (method name)
     * @param Model|int|string $with
     */
    public function associate(Model $model, string $relation, Model|int|string $with): void;

    /**
     * Excecutes a dissociate on the model model provided.
     *
     * @param Model  $model
     * @param string $relation name of the relation (method name)
     */
    public function dissociate(Model $model, string $relation): void;
}
