<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Illuminate\Support\Arr;

/**
 * Applies a bunch of scopes
 */
class Scopes extends AbstractCriteria
{

    /**
     * Should take the following format:
     *  [
     *      [ scope, parameters[] ]
     *  ]
     * @var array
     */
    protected $scopes;


    /**
     * Scopes may be passed as a set of scopesets   [ [ scope, parameters ], ... ]
     *   may also be formatted as key-value pairs   [ scope => parameters, ... ]
     * or as a list of scope names (no parameters)  [ scope, scope, ... ]
     * @param array $scopes
     * @throws \Exception
     */
    public function __construct(array $scopes)
    {
        foreach ($scopes as $scopeName => &$scopeSet) {

            // normalize each scopeset to: [ name, [ parameters ] ]

            // if a key is given, $scopeSet = parameters (and must be made an array)
            if ( ! is_numeric($scopeName)) {

                if ( ! is_array($scopeSet)) {
                    $scopeSet = [ $scopeSet ];
                }

                $scopeSet = [ $scopeName, $scopeSet ];

            } else {
                // $scopeName is not set, so the $scopeSet must contain at least the scope name
                // allow strings to be passed, assuming no parameters

                if ( ! is_array($scopeSet)) {
                    $scopeSet = [ $scopeSet, [] ];
                }
            }

            // problems if the first param is not a string
            if ( ! is_string(Arr::get($scopeSet, '0'))) {
                throw new \Exception("First parameter of scopeset must be a string (the scope name)!");
            }

            // make sure second parameter is an array
            if ( ! isset($scopeSet[1]) || empty($scopeSet[1])) {

                $scopeSet[1] = [];

            } elseif ( ! is_array($scopeSet[1])) {

                $scopeSet[1] = [ $scopeSet[1] ];
            }
        }

        unset($scopeSet);

        $this->scopes = $scopes;
    }

    /**
     * @param $model
     * @return mixed
     */
    protected function applyToQuery($model)
    {
        foreach ($this->scopes as $scopeSet) {

            $model = call_user_func_array([ $model, $scopeSet[0] ], $scopeSet[1]);
        }

        return $model;
    }
}
