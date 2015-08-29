<?php
namespace Czim\Repository\Test;

use Czim\Repository\ExtendedRepository;
use Czim\Repository\Test\Helpers\TestExtendedModel;

class ExtendedRepositoryTest extends TestCase
{
    const TABLE_NAME       = 'test_extended_models';
    const UNIQUE_FIELD     = 'unique_field';
    const SECOND_FIELD     = 'second_field';

    /**
     * @var ExtendedRepository
     */
    protected $repository;


    public function setUp()
    {
        parent::setUp();

        $this->repository = $this->app->make(Helpers\TestExtendedRepository::class);
    }

    protected function seedDatabase()
    {
        // testing table is in memory, no need to truncate
        //$this->app->make('db')->table(static::TABLE_NAME)->delete();

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
    function it_does_not_retrieve_inactive_files_and_uses_cache_by_default()
    {
        $this->assertTrue($this->repository->isCacheEnabled(), "Cache marked disabled");
        $this->assertFalse($this->repository->isInactiveIncluded(), "Inactive marked as included");

        // test if without maintenance mode, only active records are returned
        $this->assertCount(2, $this->repository->all());
        $this->assertEquals(2, $this->repository->count(), "count() value does not match all() count!");

        // set cache by looking up a record
        $this->repository->findBy(self::UNIQUE_FIELD, '999');

        // change the record without busting the cache
        $this->app['db']->table(static::TABLE_NAME)
                        ->where(self::UNIQUE_FIELD, '999')
                        ->update([ 'name' => 'changed!' ]);

        // if the change registered, the cache didn't work
        $check = $this->repository->findBy(self::UNIQUE_FIELD, '999');
        $this->assertEquals('unchanged', $check->name, "Cache did not apply, changes are seen instantly");
    }

    /**
     * @test
     * @depends it_does_not_retrieve_inactive_files_and_uses_cache_by_default
     */
    function it_retrieves_inactive_files_and_does_not_cache_in_maintenance_mode()
    {
        $this->repository->maintenance();

        $this->assertFalse($this->repository->isCacheEnabled(), "Cache not marked disabled");
        $this->assertTrue($this->repository->isInactiveIncluded(), "Inactive not marked as included");

        // test if now inactive records are returned
        $this->assertCount(3, $this->repository->all(), "Incorrect count for total in maintenance mode");

        // set cache by looking up a record
        $this->repository->findBy(self::UNIQUE_FIELD, '999');

        // change the record without busting the cache
        $this->app['db']->table(static::TABLE_NAME)
                        ->where(self::UNIQUE_FIELD, '999')
                        ->update([ 'name' => 'changed!' ]);

        // if the change registered, the cache didn't work
        $check = $this->repository->findBy(self::UNIQUE_FIELD, '999');
        $this->assertEquals('changed!', $check->name, "Result was still cached, could not see change");
    }

    /**
     * @test
     */
    function it_can_apply_and_remove_scopes_and_uses_any_set_scopes_on_queries()
    {
        // add a scope that will limit the result to 1 record
        // the Brand model has a test-scope especially for this
        $this->repository->addScope('testing', [self::UNIQUE_FIELD, '1337']);
        $this->assertEquals(1, $this->repository->count(), "Wrong result count after setting scope");
        $this->assertCount(1, $this->repository->all());

        // remove scope by name and check count
        $this->repository->removeScope('testing');
        $this->assertEquals(2, $this->repository->count(), "Wrong result count after removing scope by name");

        // set single result scope again, see if it still works
        $this->repository->addScope('testing', [self::UNIQUE_FIELD, '1337']);
        $this->assertEquals(1, $this->repository->count());

        // clear all scopes and check total again
        $this->repository->clearScopes();
        $this->assertEquals(2, $this->repository->count(), "Wrong result count after clearing all scopes");
    }

    // --------------------------------------------
    //      Criteria for extended
    // --------------------------------------------

    /**
     * @test
     */
    public function it_uses_default_criteria_when_not_configured_not_to()
    {
        // by default, the defaultCriteria() should be loaded
        $this->assertTrue($this->repository->defaultCriteria()->has('TestDefault'), "Default Criteria should include TestDefault");
        $this->assertTrue($this->repository->getCriteria()->has('TestDefault'), "Default Criteria should be in loaded getCriteria() list");
    }

    /**
     * @test
     * @depends it_uses_default_criteria_when_not_configured_not_to
     */
    function it_reapplies_criteria_only_when_changes_to_criteria_are_made()
    {
        // the idea is that a repository efficiently applies criteria,
        // leaving a query state behind that it can re-use without rebuilding
        // it, unless it MUST be rebuilt.

        // it must be rebuilt when
        //   the first call the the repository is made
        //   new criteria are pushed, criteria are removed or cleared
        //   when criteria are pushed or removed 'once'
        //   when settings have changed on the repository (cache, active, scopes)

        // create spy Criteria to check how many times we apply the criteria
        $mockCriteria = $this->makeMockCriteria($this->exactly(6), 'FirstMockCriteria');
        $this->repository->pushCriteria($mockCriteria);

        // first call, should apply +1
        $this->repository->count();

        // call without changes, should not apply
        $this->repository->count();

        // call after changing setting +1
        $this->repository->disableCache();
        $this->repository->count();

        // call after changing setting +1
        $this->repository->clearScopes();
        $this->repository->count();

        // call after pushing new criteria +1
        $mockCriteriaTwo = $this->makeMockCriteria($this->exactly(2), 'SecondMockCriteria');
        $this->repository->pushCriteria($mockCriteriaTwo, 'MockTwo');
        $this->repository->count();

        // call with once-criteria set +1 (and +1 for mock Two)
        $mockOnce = $this->makeMockCriteria($this->exactly(1), 'OnceMockCriteria');
        $this->repository->pushCriteriaOnce($mockOnce);
        $this->repository->count();

        // call with criteria removed set +1
        // but the oncemock is not-re-applied, so that's still only called 1 time!
        $this->repository->removeCriteria('MockTwo');
        $this->repository->count();

        // call with once-criteria removed if it does not exist should not make a difference
        $this->repository->removeCriteriaOnce('KeyDoesNotExist');
        $this->repository->count();
    }


    // --------------------------------------------
    //      Manipulation
    // --------------------------------------------

    /**
     * @test
     */
    function it_updates_the_active_state_of_a_record()
    {
        $this->repository->maintenance();

        $modelId = $this->repository->findBy(self::UNIQUE_FIELD, '1337')->id;
        $this->assertNotEmpty($modelId, "Test Model not found");

        // set to inactive
        $this->repository->activateRecord($modelId, false);
        $this->assertFalse($this->repository->findBy(self::UNIQUE_FIELD, '1337')->active, "Model deactivation didn't persist");

        // set to active again
        $this->repository->activateRecord($modelId);
        $this->assertTrue($this->repository->findBy(self::UNIQUE_FIELD, '1337')->active, "Model re-activation didn't persist");
    }

}
