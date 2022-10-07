<?php

namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface FindsModelsByTranslationInterface
{
    /**
     * Finds a/first model by a given translated property.
     *
     * @param string      $attribute must be translated property!
     * @param string      $value
     * @param string|null $locale
     * @param bool        $exact     = or LIKE match
     * @return TModel|null
     */
    public function findByTranslation(
        string $attribute,
        string $value,
        string $locale = null,
        bool $exact = true,
    ): ?Model;

    /**
     * Finds models by a given translated property.
     *
     * @param string      $attribute must be translated property!
     * @param string      $value
     * @param string|null $locale
     * @param bool        $exact     = or LIKE match
     * @return EloquentCollection<int, TModel>
     */
    public function findAllByTranslation(
        string $attribute,
        string $value,
        string $locale = null,
        bool $exact = true,
    ): EloquentCollection;
}
