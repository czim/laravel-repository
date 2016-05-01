<?php
namespace Czim\Repository\Enums;

use MyCLabs\Enum\Enum;

/**
 * Unique identifiers for standard Criteria that may be loaded in repositories.
 */
class CriteriaKey extends Enum
{
    const ACTIVE = 'active';    // whether to check for 'active' = 1
    const CACHE  = 'cache';     // for rememberable()
    const ORDER  = 'order';     // for order by (multiple in one optionally)
    const SCOPE  = 'scope';     // for scopes applied (multiple in one optionally)
    const WITH   = 'with';      // for eager loading
}
