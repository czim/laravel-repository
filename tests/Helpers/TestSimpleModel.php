<?php
namespace Czim\Repository\Test\Helpers;

use Illuminate\Database\Eloquent\Model;

class TestSimpleModel extends Model
{
    protected $fillable = [
        'unique_field',
        'second_field',
        'name',
        'active',
        'hidden',
    ];

    // for testing with hide/unhide attributes
    protected $hidden = [
        'hidden',
    ];


    // for testing with scopes
    public function scopeTesting($query)
    {
        return $query->whereNotNull('second_field');
    }

}
