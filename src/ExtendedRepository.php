<?php
namespace Czim\Repository;

use Czim\Repository\Contracts\ExtendedRepositoryInterface;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Czim\Repository\Enums\CriteriaKey;

/**
 * Class ExtendedRepository
 *
 * Extends BaseRepository with extra functionality:
 *
 *      - setting default criteria to apply
 *      - active record filtering
 *      - caching (requires Rememberable or custom caching Criteria)
 *      - scopes
 */
abstract class ExtendedRepository extends BaseRepository implements ExtendedRepositoryInterface
{
    /**
     * Override if model has a basic 'active' field
     *
     * @var boolean
     */
    protected $hasActive = false;

    /**
     * The column to check for if hasActive is true
     *
     * @var string
     */
    protected $activeColumn = 'active';

    /**
     * Setting: enables (remember) cache
     *
     * @var bool
     */
    protected $enableCache = false;

    /**
     * Setting: disables the active=1 check (if hasActive is true for repo)
     *
     * @var bool
     */
    protected $includeInactive = false;

    /**
     * Scopes to apply to queries
     * Must be supported by model used!
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Parameters for a given scope.
     * Note that you can only use each scope once, since parameters will be set by scope name as key.
     *
     * @var array
     */
    protected $scopeParameters = [];



    /**
     * @param Container  $app
     * @param Collection $collection
     */
    public function __construct(Container $app, Collection $collection)
    {
        parent::__construct($app, $collection);

        $this->refreshSettingDependentCriteria();
    }


    // -------------------------------------------------------------------------
    //      Criteria
    // -------------------------------------------------------------------------

    /**
     * Builds the default criteria and replaces the criteria stack to apply with
     * the default collection.
     *
     * Override to also refresh the default criteria for extended functionality.
     *
     * @return $this
     */
    public function restoreDefaultCriteria()
    {
        parent::restoreDefaultCriteria();

        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * Refreshes named criteria, so that they reflect the current repository settings
     * (for instance for updating the Active check, when includeActive has changed)
     * This also makes sure the named criteria exist at all, if they are required and were never added.
     *
     * @return $this
     */
    public function refreshSettingDependentCriteria()
    {
        if ($this->hasActive) {
            if ( ! $this->includeInactive) {
                $this->criteria->put(CriteriaKey::ACTIVE, new Criteria\Common\IsActive( $this->activeColumn ));
            } else {
                $this->criteria->forget(CriteriaKey::ACTIVE);
            }
        }

        if ($this->enableCache) {
            $this->criteria->put(CriteriaKey::CACHE, $this->getCacheCriteriaInstance());
        } else {
            $this->criteria->forget(CriteriaKey::CACHE);
        }

        if ( ! empty($this->scopes)) {
            $this->criteria->put(CriteriaKey::SCOPE, $this->getScopesCriteriaInstance());
        } else {
            $this->criteria->forget(CriteriaKey::SCOPE);
        }

        return $this;
    }

    /**
     * Returns Criteria to use for caching. Override to replace with something other
     * than Rememberable (which is used by the default Common\UseCache Criteria);
     *
     * @return Criteria\Common\UseCache
     */
    protected function getCacheCriteriaInstance()
    {
        return new Criteria\Common\UseCache();
    }


    /**
     * Returns Criteria to use for applying scopes. Override to replace with something
     * other the default Common\Scopes Criteria.
     *
     * @return Criteria\Common\Scopes
     */
    protected function getScopesCriteriaInstance()
    {
        return new Criteria\Common\Scopes( $this->convertScopesToCriteriaArray() );
    }


    // -------------------------------------------------------------------------
    //      Scopes
    // -------------------------------------------------------------------------

    /**
     * Adds a scope to enforce, overwrites with new parameters if it already exists
     *
     * @param  string $scope
     * @param  array  $parameters
     * @return self
     */
    public function addScope($scope, $parameters = [])
    {
        if ( ! in_array($scope, $this->scopes)) {

            $this->scopes[] = $scope;
        }

        $this->scopeParameters[ $scope ] = $parameters;

        $this->refreshSettingDependentCriteria();
        return $this;
    }

    /**
     * Adds a scope to enforce
     *
     * @param  string $scope
     * @return self
     */
    public function removeScope($scope)
    {
        $this->scopes = array_diff($this->scopes, [ $scope ]);

        unset($this->scopeParameters[ $scope ]);

        $this->refreshSettingDependentCriteria();
        return $this;
    }

    /**
     * Clears any currently set scopes
     *
     * @return self
     */
    public function clearScopes()
    {
        $this->scopes          = [];
        $this->scopeParameters = [];

        $this->refreshSettingDependentCriteria();
        return $this;
    }

    /**
     * Converts the tracked scopes to an array that the Scopes Common Criteria will eat.
     *
     * @return array
     */
    protected function convertScopesToCriteriaArray()
    {
        $scopes = [];

        foreach ($this->scopes as $scope) {

            if (array_key_exists($scope, $this->scopeParameters) && ! empty($this->scopeParameters[ $scope ])) {

                $scopes[] = [ $scope, $this->scopeParameters[ $scope ] ];
                continue;
            }

            $scopes[] = [ $scope, [] ];
        }

        return $scopes;
    }


    // -------------------------------------------------------------------------
    //      Maintenance mode / settings
    // -------------------------------------------------------------------------

    /**
     * Enables maintenance mode, ignoring standard limitations on model availability
     * and disables caching (if it was enabled).
     *
     * @param bool $enable
     * @return $this
     */
    public function maintenance($enable = true)
    {
        return $this->includeInactive($enable)
                    ->enableCache( ! $enable);
    }

    /**
     * Prepares repository to include inactive entries
     * (entries with the $this->activeColumn set to false)
     *
     * @param bool $enable
     * @return $this
     */
    public function includeInactive($enable = true)
    {
        $this->includeInactive = (bool) $enable;

        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * Prepares repository to exclude inactive entries
     *
     * @return $this
     */
    public function excludeInactive()
    {
        return $this->includeInactive(false);
    }

    /**
     * Returns whether inactive records are included
     *
     * @return bool
     */
    public function isInactiveIncluded()
    {
        return $this->includeInactive;
    }

    /**
     * Enables using the cache for retrieval
     *
     * @param bool $enable
     * @return $this
     */
    public function enableCache($enable = true)
    {
        $this->enableCache = (bool) $enable;

        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * Disables using the cache for retrieval
     *
     * @return $this
     */
    public function disableCache()
    {
        return $this->enableCache(false);
    }

    /**
     * Returns whether cache is currently active
     *
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->enableCache;
    }


    // -------------------------------------------------------------------------
    //      Manipulation
    // -------------------------------------------------------------------------

    /**
     * Update the active boolean for a record
     *
     * @param int     $id
     * @param boolean $active
     * @return boolean
     */
    public function activateRecord($id, $active = true)
    {
        if ( ! $this->hasActive) return false;

        $model = $this->makeModel(false);

        if ( ! ($model = $model->find($id))) return false;

        $model->{$this->activeColumn} = (boolean) $active;

        return $model->save();
    }

}
