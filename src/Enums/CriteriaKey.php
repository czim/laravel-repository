<?php
namespace Czim\Repository\Enums;

use PHPExtra\Type\Enum\Enum;

/**
 * Unique identifiers for standard Criteria that may be loaded in repositories.
 */
class CriteriaKey extends Enum
{
    const DEFAULT_VALUE = '';

    const ACTIVE = 'active';    // whether to check for 'active' = 1
    const CACHE  = 'cache';     // for rememberable()
    const ORDER  = 'order';     // for order by (multiple in one optionally)
    const SCOPE  = 'scope';     // for scopes applied (multiple in one optionally)
    const WITH   = 'with';      // for eager loading


    /**
     * Returns list of all constants defined (without _default)
     *
     * @return array
     */
    public function getList()
    {
        $reflect   = new \ReflectionClass(get_class($this));
        $constants = $reflect->getConstants();

        if (array_key_exists('DEFAULT_VALUE', $constants)) {
            unset($constants['DEFAULT_VALUE']);
        }

        return $constants;
    }

}
