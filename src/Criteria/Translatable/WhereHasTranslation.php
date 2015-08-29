<?php
namespace Czim\Repository\Criteria\Translatable;

use Czim\Repository\Criteria\AbstractCriteria;

class WhereHasTranslation extends AbstractCriteria
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var bool
     */
    protected $exact;

    /**
     * @var string
     */
    protected $operator;


    /**
     * @param string $attribute
     * @param string $value
     * @param string $locale
     * @param bool   $exact     if false, looks up as 'like' (adds %)
     */
    public function __construct($attribute, $value, $locale = null, $exact = true)
    {
        if (empty($locale)) $locale = app()->getLocale();

        if ( ! $exact && ! preg_match('#^%(.+)%$#', $value)) {
            $value = '%' . $value . '%';
        }

        $this->locale    = $locale;
        $this->attribute = $attribute;
        $this->value     = $value;
        $this->operator  = $exact ? '=' : 'LIKE';
    }


    /**
     * @param $model
     * @return mixed
     */
    protected function applyToQuery($model)
    {
        return $model->whereHas(
            'translations',
            function ($query) {

                return $query->where($this->attribute, $this->operator, $this->value)
                             ->where('locale', $this->locale);
            }
        );
    }
}
