<?php
namespace Czim\Repository\Test\Helpers;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Czim\Listify\Listify;
use Watson\Rememberable\Rememberable;

class TestExtendedModel extends Model
{
    use Translatable,
        Rememberable,
        Listify;

    protected $fillable = [
        'unique_field',
        'second_field',
        'name',
        'active',
        'position',
        'hidden',
    ];

    // for testing with hide/unhide attributes
    protected $hidden = [
        'hidden',
    ];

    protected $casts = [
        'position' => 'integer',
        'active'   => 'boolean',
    ];

    protected $translatedAttributes = [
        'translated_string',
    ];

    // for testing with scopes
    public function scopeTesting($query)
    {
        return $query->whereNotNull('second_field');
    }

    public function scopeMoreTesting($query, $field, $value)
    {
        return $query->where($field, $value);
    }
}
