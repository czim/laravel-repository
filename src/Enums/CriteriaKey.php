<?php
namespace Czim\Repository\Enums;

use PHPExtra\Type\Enum\Enum;

/**
 * Unique identifiers for standard Criteria that may be loaded in repositories.
 */
class CriteriaKey extends Enum
{
    const _default = '';

    const Active = 'active';    // whether to check for 'active' = 1
    const Cache  = 'cache';     // for rememberable()
    const Order  = 'order';     // for order by (multiple in one optionally)
    const Scope  = 'scope';     // for scopes applied (multiple in one optionally)
    const With   = 'with';      // for eager loading


    /**
     * Returns list of all constants defined (without _default)
     *
     * @return array
     */
    public function getList()
    {
        $reflect   = new \ReflectionClass(get_class($this));
        $constants = $reflect->getConstants();

        if (array_key_exists('_default', $constants)) {
            unset($constants['_default']);
        }

        return $constants;
    }

}
