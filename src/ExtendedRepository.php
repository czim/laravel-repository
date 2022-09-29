<?php

declare(strict_types=1);

namespace Czim\Repository;

use Czim\Repository\Contracts\CriteriaInterface;
use Czim\Repository\Contracts\ExtendedRepositoryInterface;
use Illuminate\Support\Collection;
use Czim\Repository\Enums\CriteriaKey;
use Psr\Container\ContainerInterface;

abstract class ExtendedRepository extends BaseRepository implements ExtendedRepositoryInterface
{
    /**
     * Override if model has a basic 'active' field.
     *
     * @var bool
     */
    protected bool $hasActive = false;

    /**
     * The column to check for if hasActive is true
     *
     * @var string
     */
    protected string $activeColumn = 'active';

    /**
     * Setting: enables (remember) cache
     *
     * @var bool
     */
    protected bool $enableCache = false;

    /**
     * Setting: disables the active=1 check (if hasActive is true for repo)
     *
     * @var bool
     */
    protected bool $includeInactive = false;

    /**
     * Scopes to apply to queries.
     * Must be supported by model used!
     *
     * @var string[]
     */
    protected array $scopes = [];

    /**
     * Parameters for a given scope.
     * Note that you can only use each scope once, since parameters will be set by scope name as key.
     *
     * @var array<int|string, mixed>
     */
    protected array $scopeParameters = [];


    /**
     * {@inheritDoc}
     */
    public function __construct(ContainerInterface $container, Collection $initialCriteria)
    {
        parent::__construct($container, $initialCriteria);

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
     */
    public function restoreDefaultCriteria(): void
    {
        parent::restoreDefaultCriteria();

        $this->refreshSettingDependentCriteria();
    }

    /**
     * Refreshes named criteria, so that they reflect the current repository settings
     * (for instance for updating the Active check, when includeActive has changed)
     * This also makes sure the named criteria exist at all, if they are required and were never added.
     */
    public function refreshSettingDependentCriteria(): void
    {
        if ($this->hasActive) {
            if (! $this->includeInactive) {
                $this->criteria->put(CriteriaKey::ACTIVE, new Criteria\Common\IsActive($this->activeColumn));
            } else {
                $this->criteria->forget(CriteriaKey::ACTIVE);
            }
        }

        if ($this->enableCache) {
            $this->criteria->put(CriteriaKey::CACHE, $this->getCacheCriteriaInstance());
        } else {
            $this->criteria->forget(CriteriaKey::CACHE);
        }

        if (! empty($this->scopes)) {
            $this->criteria->put(CriteriaKey::SCOPE, $this->getScopesCriteriaInstance());
        } else {
            $this->criteria->forget(CriteriaKey::SCOPE);
        }
    }


    // -------------------------------------------------------------------------
    //      Scopes
    // -------------------------------------------------------------------------

    /**
     * Adds a scope to enforce, overwrites with new parameters if it already exists.
     *
     * @param string                   $scope
     * @param array<int|string, mixed> $parameters
     */
    public function addScope(string $scope, array $parameters = []): void
    {
        if (! in_array($scope, $this->scopes)) {
            $this->scopes[] = $scope;
        }

        $this->scopeParameters[ $scope ] = $parameters;

        $this->refreshSettingDependentCriteria();
    }

    public function removeScope(string $scope): void
    {
        $this->scopes = array_diff($this->scopes, [$scope]);

        unset($this->scopeParameters[ $scope ]);

        $this->refreshSettingDependentCriteria();
    }

    public function clearScopes(): void
    {
        $this->scopes          = [];
        $this->scopeParameters = [];

        $this->refreshSettingDependentCriteria();
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
    public function maintenance(bool $enable = true): static
    {
        $this->includeInactive($enable);
        $this->enableCache(! $enable);

        return $this;
    }

    public function includeInactive(bool $enable = true): void
    {
        $this->includeInactive = $enable;

        $this->refreshSettingDependentCriteria();
    }

    public function excludeInactive(): void
    {
        $this->includeInactive(false);
    }

    /**
     * Returns whether inactive records are included.
     *
     * @return bool
     */
    public function isInactiveIncluded(): bool
    {
        return $this->includeInactive;
    }

    public function enableCache(bool $enable = true): void
    {
        $this->enableCache = $enable;

        $this->refreshSettingDependentCriteria();
    }

    public function disableCache(): void
    {
        $this->enableCache(false);
    }

    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
    }

    public function activateRecord(int|string $id, bool $active = true): bool
    {
        if (! $this->hasActive) {
            return false;
        }

        $model = $this->find($id);

        if (! $model) {
            return false;
        }

        $model->{$this->activeColumn} = (bool) $active;

        return $model->save();
    }


    /**
     * Converts the tracked scopes to an array that the Scopes Common Criteria will eat.
     *
     * @return array<int, array<string, mixed[]>>
     */
    protected function convertScopesToCriteriaArray(): array
    {
        $scopes = [];

        foreach ($this->scopes as $scope) {
            if (array_key_exists($scope, $this->scopeParameters) && ! empty($this->scopeParameters[ $scope ])) {
                $scopes[] = [$scope, $this->scopeParameters[ $scope ]];
                continue;
            }

            $scopes[] = [$scope, []];
        }

        return $scopes;
    }

    /**
     * Returns Criteria to use for caching. Override to replace with something other
     * than Rememberable (which is used by the default Common\UseCache Criteria);
     *
     * @return Criteria\Common\UseCache
     */
    protected function getCacheCriteriaInstance(): CriteriaInterface
    {
        return new Criteria\Common\UseCache();
    }

    /**
     * Returns Criteria to use for applying scopes. Override to replace with something
     * other the default Common\Scopes Criteria.
     *
     * @return Criteria\Common\Scopes
     */
    protected function getScopesCriteriaInstance(): CriteriaInterface
    {
        return new Criteria\Common\Scopes(
            $this->convertScopesToCriteriaArray()
        );
    }
}
