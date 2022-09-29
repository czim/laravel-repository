<?php

namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HandlesListifyModelsInterface
{
    /**
     * Updates the position for a record using Listify.
     *
     * @param int $id
     * @param int $newPosition default: top spot
     * @return Model|false
     */
    public function updatePosition(int $id, int $newPosition = 1): Model|false;
}
