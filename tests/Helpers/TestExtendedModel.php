<?php

declare(strict_types=1);

namespace Czim\Repository\Test\Helpers;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Czim\Listify\Listify;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Watson\Rememberable\Rememberable;

class TestExtendedModel extends Model
{
    use Translatable;
    use Rememberable;
    use Listify;

    /**
     * @var string[]
     */
    protected $fillable = [
        'unique_field',
        'second_field',
        'name',
        'active',
        'position',
        'hidden',
    ];

    /**
     * For testing with hide/unhide attributes.
     *
     * @var string[]
     */
    protected $hidden = [
        'hidden',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'active'   => 'boolean',
    ];

    /**
     * @var string[]
     */
    protected array $translatedAttributes = [
        'translated_string',
    ];

    /**
     * @param self|EloquentBuilder<self>|BaseBuilder $query
     * @return EloquentBuilder|BaseBuilder
     */
    public function scopeTesting(self|EloquentBuilder|BaseBuilder $query): EloquentBuilder|BaseBuilder
    {
        return $query->whereNotNull('second_field');
    }

    /**
     * @param self|EloquentBuilder<self>|BaseBuilder $query
     * @param string                                 $field
     * @param mixed                                  $value
     * @return EloquentBuilder|BaseBuilder
     */
    public function scopeMoreTesting(
        self|EloquentBuilder|BaseBuilder $query,
        string $field,
        mixed $value,
    ): EloquentBuilder|BaseBuilder {
        return $query->where($field, $value);
    }
}
