<?php
namespace Czim\Repository\Contracts;

use Illuminate\Support\Collection;

interface ExtendedRepositoryInterface
{
    /**
     * Refreshes named criteria, so that they reflect the current repository settings
     * (for instance for updating the Active check, when includeActive has changed)
     * This also makes sure the named criteria exist at all, if they are required and were never added.
     *
     * @return $this
     */
    public function refreshSettingDependentCriteria();

    /**
     * Adds a scope to enforce, overwrites with new parameters if it already exists
     *
     * @param  string $scope
     * @param  array  $parameters
     * @return self
     */
    public function addScope($scope, $parameters = []);

    /**
     * Adds a scope to enforce
     *
     * @param  string $scope
     * @return self
     */
    public function removeScope($scope);

    /**
     * Clears any currently set scopes
     *
     * @return self
     */
    public function clearScopes();

    /**
     * Enables maintenance mode, ignoring standard limitations on model availability
     *
     * @param bool $enable
     * @return $this
     */
    public function maintenance($enable = true);

    /**
     * Prepares repository to include inactive entries
     * (entries with the $this->activeColumn set to false)
     *
     * @param bool $enable
     * @return $this
     */
    public function includeInactive($enable = true);

    /**
     * Prepares repository to exclude inactive entries
     */
    public function excludeInactive();

    /**
     * Enables using the cache for retrieval
     *
     * @param bool $enable
     * @return $this
     */
    public function enableCache($enable = true);

    /**
     * Disables using the cache for retrieval
     */
    public function disableCache();

    /**
     * Returns whether inactive records are included
     *
     * @return bool
     */
    public function isInactiveIncluded();

    /**
     * Returns whether cache is currently active
     *
     * @return bool
     */
    public function isCacheEnabled();
}
