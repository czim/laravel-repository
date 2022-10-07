<?php

declare(strict_types=1);

namespace Czim\Repository\Traits;

use Czim\Listify\Contracts\ListifyInterface;
use Czim\Repository\Contracts\HandlesListifyModelsInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @see HandlesListifyModelsInterface
 */
trait HandlesListifyModelsTrait
{
    /**
     * Updates the position for a record using Listify.
     *
     * @param int $id
     * @param int $newPosition default: top spot
     * @return TModel|false
     */
    public function updatePosition(int $id, int $newPosition = 1): Model|false
    {
        $model = $this->makeModel(false);

        $model = $model->find($id);

        if (! $model) {
            return false;
        }

        $this->assertModelHasListify($model);

        /** @var ListifyInterface $model */
        $model->setListPosition($newPosition);

        return $model;
    }

    protected function assertModelHasListify(Model $model): void
    {
        if (! method_exists($model, 'setListPosition')) {
            throw new InvalidArgumentException('Method can only be used on Models with the Listify trait');
        }
    }
}
