<?php
namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HandlesEloquentRelationManipulationInterface
{
    /**
     * Executes a sync on the model provided
     *
     * @param  Model  $model
     * @param  string $relation name of the relation (method name)
     * @param  array  $ids      list of id's to connect to
     * @param bool    $detaching
     * @return
     */
    public function sync(Model $model, $relation, $ids, $detaching = true);

    /**
     * Executes an attach on the model provided
     *
     * @param  Model   $model
     * @param  string  $relation name of the relation (method name)
     * @param  int     $id
     * @param  array   $attributes
     * @param  boolean $touch
     */
    public function attach(Model $model, $relation, $id, array $attributes = array(), $touch = true);

    /**
     * Executes a detach on the model provided
     *
     * @param  Model   $model
     * @param  string  $relation name of the relation (method name)
     * @param  array   $ids
     * @param  boolean $touch
     * @return
     * @internal param array $attributes
     */
    public function detach(Model $model, $relation, $ids = array(), $touch = true);

    /**
     * Excecutes an associate on the model model provided
     *
     * @param  Model  $model
     * @param  string $relation name of the relation (method name)
     * @param  mixed  $with
     * @return boolean
     */
    public function associate(Model $model, $relation, $with);

    /**
     * Excecutes a dissociate on the model model provided
     *
     * @param  Model  $model
     * @param  string $relation name of the relation (method name)
     * @param  mixed  $from
     * @return boolean
     */
    public function dissociate(Model $model, $relation, $from);
}
