<?php
namespace Czim\Repository\Contracts;

use Illuminate\Contracts\Support\Htmlable;
use Countable;
use ArrayAccess;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;

interface LengthAwarePaginatorInterface extends
    LengthAwarePaginatorContract,
    Arrayable,
    ArrayAccess,
    Countable,
    IteratorAggregate,
    JsonSerializable,
    Jsonable,
    Htmlable
{

}
