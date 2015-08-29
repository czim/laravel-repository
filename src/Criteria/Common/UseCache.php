<?php
namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Watson\Rememberable\Query\Builder;
use Watson\Rememberable\Rememberable;

/**
 * Configure default cache duration in config: cache.ttl
 */
class UseCache extends AbstractCriteria
{
    const CACHE_DEFAULT_TTL = 15;

    /**
     * @var int|null
     */
    protected $timeToLive;

    /**
     * @param null|int $timeToLive  in minutes
     */
    public function __construct($timeToLive = null)
    {
        if (empty($timeToLive)) {
            $timeToLive = config('cache.ttl') ?: static::CACHE_DEFAULT_TTL;
        }

        $this->timeToLive = $timeToLive;
    }

    /**
     * @param Rememberable|Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        return $model->remember($this->timeToLive);
    }
}
