<?php

declare(strict_types=1);

namespace Czim\Repository\Traits;

use Czim\Repository\Contracts\HandlesEloquentSavingInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * The point of this is to provide Eloquent saving through some
 * intermediate object (i.e. a Repository) to make model manipulation
 * easier to test/mock.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @see HandlesEloquentSavingInterface
 */
trait HandlesEloquentSavingTrait
{
    /**
     * @param TModel               $model
     * @param array<string, mixed> $options
     * @return bool
     */
    public function save(Model $model, array $options = []): bool
    {
        return $model->save($options);
    }
}
