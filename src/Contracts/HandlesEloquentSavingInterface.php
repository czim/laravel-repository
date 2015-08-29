<?php

namespace Czim\Repository\Contracts;
use Illuminate\Database\Eloquent\Model;


/**
 * The point of this is to provide Eloquent saving through some
 * intermediate object (i.e. a Repository) to make model manipulation
 * easier to test/mock.
 */
interface HandlesEloquentSavingInterface
{
    /**
     * Executes a save on the model provided
     *
     * @param  Model $model
     * @param  array $options
     * @return bool
     */
    public function save(Model $model, array $options = array());
}
