<?php

declare(strict_types=1);

namespace Czim\Repository\Traits;

use Czim\Repository\Contracts\FindsModelsByTranslationInterface;
use Czim\Repository\Criteria\Translatable\WhereHasTranslation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @see FindsModelsByTranslationInterface
 */
trait FindsModelsByTranslationTrait
{
    /**
     * Finds a/first model by a given translated property.
     *
     * @param string      $attribute must be translated property!
     * @param string      $value
     * @param string|null $locale
     * @param bool        $exact     = or LIKE match
     * @return Model|null
     */
    public function findByTranslation(string $attribute, string $value, string $locale = null, bool $exact = true): ?Model
    {
        $this->pushCriteriaOnce( new WhereHasTranslation($attribute, $value, $locale, $exact) );

        return $this->first();
    }

    /**
     * Finds models by a given translated property.
     *
     * @param string      $attribute must be translated property!
     * @param string      $value
     * @param string|null $locale
     * @param bool        $exact     = or LIKE match
     * @return EloquentCollection<int, Model>
     */
    public function findAllByTranslation(string $attribute, string $value, string $locale = null, bool $exact = true): EloquentCollection
    {
        $this->pushCriteriaOnce( new WhereHasTranslation($attribute, $value, $locale, $exact) );

        return $this->all();
    }
}
