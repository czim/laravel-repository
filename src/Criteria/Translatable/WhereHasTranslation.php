<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Translatable;

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
class WhereHasTranslation extends AbstractCriteria
{
    protected string $locale;
    protected string $attribute;
    protected string $value;
    protected bool $exact;
    protected string $operator;

    /**
     * @param string      $attribute
     * @param string      $value
     * @param string|null $locale
     * @param bool        $exact if false, looks up as 'like' (adds %)
     */
    public function __construct(
        string $attribute,
        string $value,
        string $locale = null,
        bool $exact = true,
    ) {
        $locale ?: app()->getLocale();

        if (! $exact && ! preg_match('#^%(.+)%$#', $value)) {
            $value = '%' . $value . '%';
        }

        $this->locale    = $locale;
        $this->attribute = $attribute;
        $this->value     = $value;
        $this->operator  = $exact ? '=' : 'LIKE';
    }

    /**
     * @param TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel> $model
     * @return TModel|Relation<TRelated>|DatabaseBuilder|EloquentBuilder<TModel>
     */
    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        return $model->whereHas(
            'translations',
            fn (EloquentBuilder|Relation $query) => $query
                ->where($this->attribute, $this->operator, $this->value)
                ->where('locale', $this->locale)
        );
    }
}
