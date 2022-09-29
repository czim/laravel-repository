<?php

namespace Czim\Repository\Contracts;

interface ExtendedRepositoryInterface
{
    /**
     * Refreshes named criteria, so that they reflect the current repository settings.
     *
     * (For instance for updating the Active check, when includeActive has changed)
     * This also makes sure the named criteria exist at all, if they are required and were never added.
     */
    public function refreshSettingDependentCriteria(): void;

    /**
     * Adds a scope to enforce, overwrites with new parameters if it already exists.
     *
     * @param string                   $scope
     * @param array<int|string, mixed> $parameters
     */
    public function addScope(string $scope, array $parameters = []): void;

    public function removeScope(string $scope): void;

    public function clearScopes(): void;

    /**
     * Enables maintenance mode, ignoring standard limitations on model availability
     *
     * @param bool $enable
     * @return $this
     */
    public function maintenance(bool $enable = true): static;

    /**
     * Prepares repository to include inactive entries
     * (entries with the $this->activeColumn set to false)
     *
     * @param bool $enable
     */
    public function includeInactive(bool $enable = true): void;

    /**
     * Prepares repository to exclude inactive entries.
     */
    public function excludeInactive(): void;

    /**
     * Enables using the cache for retrieval.
     *
     * @param bool $enable
     */
    public function enableCache(bool $enable = true): void;

    /**
     * Disables using the cache for retrieval.
     */
    public function disableCache(): void;

    /**
     * Returns whether inactive records are included.
     *
     * @return bool
     */
    public function isInactiveIncluded(): bool;

    /**
     * Returns whether cache is currently active.
     *
     * @return bool
     */
    public function isCacheEnabled(): bool;
}
