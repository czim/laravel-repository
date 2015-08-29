<?php
namespace Czim\Repository\Traits;

use Czim\Repository\Criteria\Translatable\WhereHasTranslation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait FindsModelsByTranslationTrait
{
    /**
     * Finds a/first model by a given translated property
     *
     * @param string $attribute must be translated property!
     * @param string $value
     * @param string $locale
     * @param bool   $exact     = or LIKE match
     * @return Model|null
     */
    public function findByTranslation($attribute, $value, $locale = null, $exact = true)
    {
        $this->pushCriteriaOnce( new WhereHasTranslation($attribute, $value, $locale, $exact) );

        return $this->first();
    }

    /**
     * Finds models by a given translated property
     *
     * @param string $attribute must be translated property!
     * @param string $value
     * @param string $locale
     * @param bool   $exact     = or LIKE match
     * @return Collection
     */
    public function findAllByTranslation($attribute, $value, $locale = null, $exact = true)
    {
        $this->pushCriteriaOnce( new WhereHasTranslation($attribute, $value, $locale, $exact) );

        return $this->all();
    }
}
