<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Applies a bunch of scopes.
 */
class Scopes extends AbstractCriteria
{
    /**
     * Has the following format:
     *  [
     *      [ scope, parameters[] ]
     *  ]
     *
     * @var array<int, array{0: string, 1: mixed[]}>
     */
    protected array $scopes;


    /**
     * Scopes may be passed as a set of scopesets     [ [ scope, parameters ], ... ]
     *   may also be formatted as key-value pairs     [ scope => parameters, ... ]
     *   or as a list of scope names (no parameters)  [ scope, scope, ... ]
     *
     * @param string[]|array<string, mixed[]>|array<int, array<string, mixed[]>> $scopes
     */
    public function __construct(array $scopes)
    {
        foreach ($scopes as $scopeName => &$scopeSet) {
            // Normalize each scopeset to: [ name, [ parameters ] ].

            // If a key is given, $scopeSet = parameters (and must be made an array).
            if (! is_numeric($scopeName)) {
                if (! is_array($scopeSet)) {
                    $scopeSet = [$scopeSet];
                }

                $scopeSet = [$scopeName, $scopeSet];
            } else {
                // $scopeName is not set, so the $scopeSet must contain at least the scope name.
                // Allow strings to be passed, assuming no parameters.
                if (! is_array($scopeSet)) {
                    $scopeSet = [$scopeSet, []];
                }
            }

            // Problems if the first param is not a string.
            if (! is_string(Arr::get($scopeSet, '0'))) {
                throw new InvalidArgumentException('First parameter of scopeset must be a string (the scope name)!');
            }

            // Make sure second parameter is an array.
            if (empty($scopeSet[1])) {
                $scopeSet[1] = [];
            } elseif (! is_array($scopeSet[1])) {
                $scopeSet[1] = [$scopeSet[1]];
            }
        }

        unset($scopeSet);

        $this->scopes = $scopes;
    }

    protected function applyToQuery(
        Model|Relation|DatabaseBuilder|EloquentBuilder $model
    ): Model|Relation|DatabaseBuilder|EloquentBuilder {
        foreach ($this->scopes as $scopeSet) {
            $model = call_user_func_array([$model, $scopeSet[0]], $scopeSet[1]);
        }

        return $model;
    }
}
