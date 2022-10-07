<?php

declare(strict_types=1);

namespace Czim\Repository\Enums;

use MyCLabs\Enum\Enum;

/**
 * Unique identifiers for standard Criteria that may be loaded in repositories.
 *
 * @method static static ACTIVE()
 * @method static static CACHE()
 * @method static static ORDER()
 * @method static static SCOPE()
 * @method static static WITH()
 *
 * @extends Enum<string>
 */
class CriteriaKey extends Enum
{
    public const ACTIVE = 'active';    // whether to check for 'active' = 1
    public const CACHE  = 'cache';     // for rememberable()
    public const ORDER  = 'order';     // for order by (multiple in one optionally)
    public const SCOPE  = 'scope';     // for scopes applied (multiple in one optionally)
    public const WITH   = 'with';      // for eager loading
}
