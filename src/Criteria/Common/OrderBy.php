<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TRelated of \Illuminate\Database\Eloquent\Model
 *
 * @extends AbstractCriteria<TModel, TRelated>
 */
class OrderBy extends AbstractCriteria
{
    private const DEFAULT_DIRECTION = 'asc';

    /**
     * @var array<string, string> column => direction
     */
    protected array $orderClauses = [];

    /**
     * @param string|string[] $columnOrArray   may be either a single column, in which the second parameter
     *                                         is used for direction, or an array of 'column' => 'direction' values
     * @param string          $direction       'asc'/'desc'
     */
    public function __construct(
        string|array $columnOrArray,
        string $direction = self::DEFAULT_DIRECTION,
    ) {
        $this->orderClauses = $this->normalizeOrderClauses($columnOrArray, $direction);
    }

    /**
     * @param TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel> $model
     * @return TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel>
     */
    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        foreach ($this->orderClauses as $column => $direction) {
            $model = $model->orderBy($column, $direction);
        }

        return $model;
    }

    /**
     * @param string|string[] $columnOrArray
     * @param string          $direction
     * @return array<string, string>
     */
    protected function normalizeOrderClauses(string|array $columnOrArray, string $direction): array
    {
        if (is_string($columnOrArray)) {
            return [
                $columnOrArray => $direction,
            ];
        }

        $newColumns = [];

        foreach ($columnOrArray as $column => $direction) {
            if (is_numeric($column)) {
                $column    = $direction;
                $direction = self::DEFAULT_DIRECTION;
            }

            $newColumns[$column] = $direction;
        }

        return $newColumns;
    }
}
