<?php

declare(strict_types=1);

namespace Czim\Repository\Test\Helpers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseBuilder;

class TestSimpleModel extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'unique_field',
        'second_field',
        'name',
        'active',
        'hidden',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'hidden',
    ];

    /**
     * @param Model|EloquentBuilder|BaseBuilder $query
     * @return EloquentBuilder|BaseBuilder
     */
    public function scopeTesting($query): EloquentBuilder|BaseBuilder
    {
        return $query->whereNotNull('second_field');
    }
}
