<?php

declare(strict_types=1);

namespace Czim\Repository\Test;

use Czim\Repository\ExtendedRepository;
use Czim\Repository\Test\Helpers\TestExtendedModel;

class ExtendedRepositoryTest extends TestCase
{
    protected const TABLE_NAME       = 'test_extended_models';
    protected const UNIQUE_FIELD     = 'unique_field';
    protected const SECOND_FIELD     = 'second_field';

    protected ?ExtendedRepository $repository = null;


    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(Helpers\TestExtendedRepository::class);
    }

    protected function seedDatabase(): void
    {
        TestExtendedModel::create([
            'unique_field' => '999',
            'second_field' => null,
            'name'         => 'unchanged',
            'active'       => true,
            'hidden'       => 'invisible',
        ]);

        TestExtendedModel::create([
            'unique_field' => '1234567',
            'second_field' => '434',
            'name'         => 'random name',
            'active'       => false,
            'hidden'       => 'cannot see me',
        ]);

        TestExtendedModel::create([
            'unique_field' => '1337',
            'second_field' => '12345',
            'name'         => 'special name',
            'active'       => true,
            'hidden'       => 'where has it gone?',
        ]);
    }


    // --------------------------------------------
    //      Settings / caching / scopes
    // --------------------------------------------

    /**
     * @test
     */
    public function it_does_not_retrieve_inactive_files_and_uses_cache_by_default(): void
    {
        static::assertTrue($this->repository->isCacheEnabled(), 'Cache marked disabled');
        static::assertFalse($this->repository->isInactiveIncluded(), 'Inactive marked as included');

        // Test if without maintenance mode, only active records are returned.
        static::assertCount(2, $this->repository->all());
        static::assertEquals(2, $this->repository->count(), 'count() value does not match all() count!');

        // Set cache by looking up a record.
        $this->repository->findBy(self::UNIQUE_FIELD, '999');

        // Change the record without busting the cache.
        $this->app['db']
            ->table(static::TABLE_NAME)
            ->where(self::UNIQUE_FIELD, '999')
            ->update([ 'name' => 'changed!' ]);

        // If the change registered, the cache didn't work.
        $check = $this->repository->findBy(self::UNIQUE_FIELD, '999');
        static::assertEquals('unchanged', $check->name, 'Cache did not apply, changes are seen instantly');
    }

    /**
     * @test
     * @depends it_does_not_retrieve_inactive_files_and_uses_cache_by_default
     */
    public function it_retrieves_inactive_files_and_does_not_cache_in_maintenance_mode(): void
    {
        $this->repository->maintenance();

        static::assertFalse($this->repository->isCacheEnabled(), 'Cache not marked disabled');
        static::assertTrue($this->repository->isInactiveIncluded(), 'Inactive not marked as included');

        // Test if now inactive records are returned.
        static::assertCount(3, $this->repository->all(), 'Incorrect count for total in maintenance mode');

        // Set cache by looking up a record.
        $this->repository->findBy(self::UNIQUE_FIELD, '999');

        // Change the record without busting the cache.
        $this->app['db']->table(static::TABLE_NAME)
            ->where(self::UNIQUE_FIELD, '999')
            ->update([ 'name' => 'changed!' ]);

        // If the change registered, the cache didn't work.
        $check = $this->repository->findBy(self::UNIQUE_FIELD, '999');
        static::assertEquals('changed!', $check->name, 'Result was still cached, could not see change');
    }

    /**
     * @test
     */
    public function it_can_apply_and_remove_scopes_and_uses_any_set_scopes_on_queries(): void
    {
        // Add a scope that will limit the result to 1 record.
        // The Supplier model has a test-scope especially for this.
        $this->repository->addScope('moreTesting', [self::UNIQUE_FIELD, '1337']);
        static::assertEquals(1, $this->repository->count(), 'Wrong result count after setting scope');
        static::assertCount(1, $this->repository->all());

        // Remove scope by name and check count.
        $this->repository->removeScope('moreTesting');
        static::assertEquals(2, $this->repository->count(), 'Wrong result count after removing scope by name');

        // Set single result scope again, see if it still works.
        $this->repository->addScope('moreTesting', [self::UNIQUE_FIELD, '1337']);
        static::assertEquals(1, $this->repository->count());

        // Clear all scopes and check total again.
        $this->repository->clearScopes();
        static::assertEquals(2, $this->repository->count(), 'Wrong result count after clearing all scopes');
    }

    // --------------------------------------------
    //      Criteria for extended
    // --------------------------------------------

    /**
     * @test
     */
    public function it_uses_default_criteria_when_not_configured_not_to(): void
    {
        // By default, the defaultCriteria() should be loaded.
        static::assertTrue(
            $this->repository->defaultCriteria()->has('TestDefault'),
            'Default Criteria should include TestDefault'
        );

        static::assertTrue(
            $this->repository->getCriteria()->has('TestDefault'),
            'Default Criteria should be in loaded getCriteria() list'
        );
    }

    /**
     * @test
     * @depends it_uses_default_criteria_when_not_configured_not_to
     */
    public function it_reapplies_criteria_only_when_changes_to_criteria_are_made(): void
    {
        // The idea is that a repository efficiently applies criteria, leaving a query state behind that
        // it can re-use without rebuilding it, unless it MUST be rebuilt.

        // It must be rebuilt when ...
        //   ... the first call the the repository is made.
        //   ... new criteria are pushed, criteria are removed or cleared.
        //   ... when criteria are pushed or removed 'once'.
        //   ... when settings have changed on the repository (cache, active, scopes).

        // Create spy Criteria to check how many times we apply the criteria.
        $mockCriteria = $this->makeMockCriteria(6);
        $this->repository->pushCriteria($mockCriteria);

        // First call, should apply +1.
        $this->repository->count();

        // Call without changes, should not apply.
        $this->repository->count();

        // Call after changing setting +1.
        $this->repository->disableCache();
        $this->repository->count();

        // Call after changing setting +1.
        $this->repository->clearScopes();
        $this->repository->count();

        // Call after pushing new criteria +1.
        $mockCriteriaTwo = $this->makeMockCriteria('twice');
        $this->repository->pushCriteria($mockCriteriaTwo, 'MockTwo');
        $this->repository->count();

        // Call with once-criteria set +1 (and +1 for mock Two).
        $mockOnce = $this->makeMockCriteria('once');
        $this->repository->pushCriteriaOnce($mockOnce);
        $this->repository->count();

        // Call with criteria removed set +1, but the oncemock is not-re-applied, so that's still only called 1 time!
        $this->repository->removeCriteria('MockTwo');
        $this->repository->count();

        // Call with once-criteria removed if it does not exist should not make a difference.
        $this->repository->removeCriteriaOnce('KeyDoesNotExist');
        $this->repository->count();
    }


    // --------------------------------------------
    //      Manipulation
    // --------------------------------------------

    /**
     * @test
     */
    public function it_updates_the_active_state_of_a_record(): void
    {
        $this->repository->maintenance();

        $modelId = $this->repository->findBy(self::UNIQUE_FIELD, '1337')->id;
        static::assertNotEmpty($modelId, 'Test Model not found');

        // Set to inactive.
        $this->repository->activateRecord($modelId, false);
        static::assertFalse(
            $this->repository->findBy(self::UNIQUE_FIELD, '1337')->active,
            "Model deactivation didn't persist"
        );

        // Set to active again.
        $this->repository->activateRecord($modelId);
        static::assertTrue(
            $this->repository->findBy(self::UNIQUE_FIELD, '1337')->active,
            "Model re-activation didn't persist"
        );
    }
}
