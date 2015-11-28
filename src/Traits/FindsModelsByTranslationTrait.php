<?php
namespace Czim\Repository\Traits;

use Czim\Repository\Criteria\Translatable\WhereHasTranslation;

trait FindsModelsByTranslationTrait
{
    /**
     * Finds a/first model by a given translated property
     *
     * @param string $attribute must be translated property!
     * @param string $value
     * @param string $locale
     * @param bool   $exact     = or LIKE match
     * @return \Illuminate\Database\Eloquent\Model|null
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
     * @return \Illuminate\Support\Collection
     */
    public function findAllByTranslation($attribute, $value, $locale = null, $exact = true)
    {
        $this->pushCriteriaOnce( new WhereHasTranslation($attribute, $value, $locale, $exact) );

        return $this->all();
    }
}
