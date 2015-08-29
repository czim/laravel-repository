<?php
namespace Czim\Repository\Test;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Test\Helpers\TestSimpleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BaseRepositoryTest extends TestCase
{
    const TABLE_NAME   = 'test_simple_models';
    const UNIQUE_FIELD = 'unique_field';
    const SECOND_FIELD = 'second_field';

    /**
     * @var BaseRepositoryInterface
     */
    protected $repository;


    public function setUp()
    {
        parent::setUp();

        $this->repository = $this->app->make(Helpers\TestBaseRepository::class);
    }

    protected function seedDatabase()
    {
        // testing table is in memory, no need to truncate
        //$this->app->make('db')->table(static::TABLE_NAME)->delete();

        TestSimpleModel::create([
            'unique_field' => '999',
            'second_field' => null,
            'name'         => 'unchanged',
            'active'       => true,
        ]);

        TestSimpleModel::create([
            'unique_field' => '1234567',
            'second_field' => '434',
            'name'         => 'random name',
            'active'       => false,
        ]);

        TestSimpleModel::create([
            'unique_field' => '1337',
            'second_field' => '12345',
            'name'         => 'special name',
            'active'       => true,
        ]);
    }


    // --------------------------------------------
    //      Retrieval
    // --------------------------------------------

    /**
     * @test
     */
    function it_handles_basic_retrieval_operations()
    {
        // all
        $result = $this->repository->all();
        $this->assertInstanceOf(Collection::class, $result, "Did not get Collection for all()");
        $this->assertCount(3, $result, "Did not get correct count for all()");

        // get an id that we can use find on
        $someId = $result->first()->id;
        $this->assertNotEmpty($someId, "Did not get a valid Model's id from the all() result");

        // find
        $this->assertInstanceOf(Model::class, $this->repository->find($someId), "Did not get Model for find()");

        // count
        $this->assertEquals(3, $this->repository->count(), "Did not get correct result for count()");

        // first
        $this->assertInstanceOf(Model::class, $this->repository->first(), "Did not get Model for first() on all");

        // findBy
        $this->assertInstanceOf(Model::class, $this->repository->findBy(self::UNIQUE_FIELD, '1337'), "Did not get Model for findBy() for unique field value");

        // findAllBy
        $this->assertCount(2, $this->repository->findAllBy('active', true), "Did not get correct count for result for findAllBy(active = true)");

        // paginate
        $this->assertCount(2, $this->repository->paginate(2), "Did not get correct count for paginate()");

        // lists
        $list = $this->repository->lists(self::UNIQUE_FIELD);
        $this->assertCount(3, $list, "Did not get correct array count for lists()");
        $this->assertContains('1337', $list, "Did not get correct array content for lists()");
    }

    /**
     * @test
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    function it_throws_an_exception_when_findorfail_does_not_find_anything()
    {
        $this->repository->findOrFail(895476);
    }

    /**
     * @test
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    function it_throws_an_exception_when_firstorfail_does_not_find_anything()
    {
        // make sure we won't find anything
        $mockCriteria = $this->makeMockCriteria($this->exactly(1), 'MockCriteria', function($query) {
            return $query->where('name', 'some name that certainly does not exist');
        });
        $this->repository->pushCriteria($mockCriteria);

        $this->repository->firstOrFail();
    }

    /**
     * Bosnadev's findWhere() method
     * @test
     */
    function it_can_perform_a_findwhere_with_custom_parameters()
    {
        // simple field/value combo's by key
        $this->assertCount(
            1,
            $this->repository->findWhere([
                self::UNIQUE_FIELD => '1234567',
                self::SECOND_FIELD => '434',
            ]),
            "findWhere() with field/value combo failed (incorrect match count)"
        );

        // arrays with field/value sets
        $this->assertCount(
            1,
            $this->repository->findWhere([
                [ self::UNIQUE_FIELD, '1234567' ],
                [ self::SECOND_FIELD, '434' ],
            ]),
            "findWhere() with field/value sets failed (incorrect match count)"
        );

        // arrays with field/operator/value sets
        $this->assertCount(
            1,
            $this->repository->findWhere([
                [ self::UNIQUE_FIELD, 'LIKE', '%234567' ],
                [ self::SECOND_FIELD, 'LIKE', '43%' ],
            ]),
            "findWhere() with field/operator/value sets failed (incorrect match count)"
        );

        // closure send directly to the model's where() method
        $this->assertCount(
            1,
            $this->repository->findWhere([ function($query) {
                return $query->where(self::UNIQUE_FIELD, 'LIKE', '%234567');
            }]),
            "findWhere() with Closure callback failed (incorrect match count)"
        );
    }

    /**
     * @test
     */
    function it_can_perform_find_and_all_lookups_with_a_callback_for_custom_queries()
    {
        // allCallback
        $result = $this->repository->allCallback( function($query) {
            return $query->where(self::UNIQUE_FIELD, '1337');
        });
        $this->assertCount(1, $result, "Wrong count for allCallback()");


        // findCallback
        $result = $this->repository->findCallback( function($query) {
            return $query->where(self::UNIQUE_FIELD, '1337');
        });
        $this->assertEquals('1337', $result->{self::UNIQUE_FIELD}, "Wrong result for findCallback()");
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throw_an_exception_if_the_callback_for_custom_queries_is_incorrect()
    {
        $this->repository->allCallback( function() {
            return 'incorrect return value';
        });
    }


    // --------------------------------------------
    //      Manipulation
    // --------------------------------------------

    /**
     * @test
     * @depends it_handles_basic_retrieval_operations
     */
    function it_handles_basic_manipulation_operations()
    {
        // update existing
        $someId = $this->repository->findBy(self::UNIQUE_FIELD, '999')->id;
        $this->assertNotEmpty($someId, "Did not get a valid Model's id from the findBy(unique_field) result");
        $this->repository->update([ 'name' => 'changed it!' ], $someId);
        $this->assertEquals('changed it!', $this->repository->findBy(self::UNIQUE_FIELD, '999')->name, "Change did not apply after update()");

        // create new
        $model = $this->repository->create([
            self::UNIQUE_FIELD => '313',
            'name'             => 'New Model',
        ]);
        $this->assertInstanceOf(Model::class, $model, "Create() response is not a Model");
        $this->assertNotEmpty($model->id, "Model does not have an id (likely story)");
        $this->seeInDatabase(static::TABLE_NAME, [ 'id' => $model->id, self::UNIQUE_FIELD => '313', 'name' => 'New Model' ]);
        $this->assertEquals(4, $this->repository->count(), "Total count after creating new does not match");

        // delete
        $this->assertEquals(1, $this->repository->delete($model->id), "Delete() call did not return succesful count");
        $this->assertEquals(3, $this->repository->count(), "Total count after deleting does not match");
        $this->notSeeInDatabase(static::TABLE_NAME, [ 'id' => $model->id ]);
        unset($model);
    }


    // --------------------------------------------
    //      Criteria
    // --------------------------------------------

    /**
     * @test
     */
    function it_returns_and_can_restore_default_criteria()
    {
        $this->assertTrue($this->repository->defaultCriteria()->isEmpty(), "Defaultcriteria is not empty");

        $this->repository->pushCriteria($this->makeMockCriteria($this->never(), 'MockCriteria'));
        $this->assertCount(1, $this->repository->getCriteria(), "getCriteria() count incorrect after pushing new Criteria");

        $this->repository->restoreDefaultCriteria();
        $this->assertTrue($this->repository->getCriteria()->isEmpty(), "getCriteria() not empty after restoring default Criteria()");
    }

    /**
     * @test
     * @depends it_handles_basic_retrieval_operations
     */
    function it_takes_criteria_and_handles_basic_criteria_manipulation()
    {
        // clear all criteria, see if none are applied
        $this->repository->clearCriteria();
        $this->assertTrue($this->repository->getCriteria()->isEmpty(), "getCriteria() not empty after clearCriteria()");
        $this->assertRegExp(
            "#^select \* from [`\"]" . static::TABLE_NAME ."[`\"]$#i",
            $this->repository->query()->toSql(),
            "Query SQL should be totally basic after clearCriteria()"
        );


        // add new criteria, see if it is applied
        $criteria = $this->makeMockCriteria($this->exactly(2), 'MockCriteria', function($query) {
            return $query->where(self::UNIQUE_FIELD, '1337');
        });
        $this->repository->pushCriteria($criteria, 'TemporaryCriteria');
        $this->assertCount(1, $this->repository->getCriteria(), "getCriteria() count incorrect after pushing new Criteria");
        $this->assertRegExp(
            "#where [`\"]" . self::UNIQUE_FIELD . "[`\"] =#i",
            $this->repository->query()->toSql(),
            "Query SQL should be altered by pushing Criteria"
        );

        // set repository to ignore criteria, see if they do not get applied
        $this->repository->ignoreCriteria();
        $this->assertNotRegExp(
            "#where [`\"]" . self::UNIQUE_FIELD . "[`\"] =#i",
            $this->repository->query()->toSql(),
            "Query SQL should be altered by pushing Criteria"
        );
        $this->repository->ignoreCriteria(false);


        // remove criteria once, see if it is not applied
        $this->repository->removeCriteriaOnce('TemporaryCriteria');
        $this->assertCount(1, $this->repository->getCriteria(), "getCriteria() should still have a count of one if only removing temporarily");
        $this->assertRegExp(
            "#^select \* from [`\"]" . static::TABLE_NAME ."[`\"]$#i",
            $this->repository->query()->toSql(),
            "Query SQL should be totally basic while removing Criteria once"
        );
        $this->assertRegExp(
            "#where [`\"]" . self::UNIQUE_FIELD . "[`\"] =#i",
            $this->repository->query()->toSql(),
            "Query SQL should be altered again on next call after removing Criteria once"
        );


        // override criteria once, see if it is overridden succesfully and not called
        $secondCriteria = $this->makeMockCriteria($this->exactly(1), 'SecondCriteria', function($query) {
            return $query->where(self::SECOND_FIELD, '12345');
        });
        $this->repository->pushCriteriaOnce($secondCriteria, 'TemporaryCriteria');
        $sql = $this->repository->query()->toSql();
        $this->assertNotRegExp(
            "#where [`\"]" . self::UNIQUE_FIELD . "[`\"] =#i",
            $sql,
            "Query SQL should not be built using first TemporaryCriteria"
        );
        $this->assertRegExp(
            "#where [`\"]" . self::SECOND_FIELD . "[`\"] =#i",
            $sql,
            "Query SQL should be built using the overriding Criteria"
        );


        // remove specific criteria, see if it is not applied
        $this->repository->removeCriteria('TemporaryCriteria');
        $this->assertTrue($this->repository->getCriteria()->isEmpty(), "getCriteria() not empty after removing Criteria");
        $this->assertRegExp(
            "#^select \* from [`\"]" . static::TABLE_NAME ."[`\"]$#i",
            $this->repository->query()->toSql(),
            "Query SQL should be totally basic after removing Criteria"
        );


        // override criteria once, see if it is changed
        $criteria = $this->makeMockCriteria($this->exactly(1), 'MockCriteria', function($query) {
            return $query->where(self::UNIQUE_FIELD, '1337');
        });
        $this->repository->pushCriteriaOnce($criteria);
        $this->assertTrue($this->repository->getCriteria()->isEmpty(), "getCriteria() not empty with only once Criteria pushed");
        $this->assertRegExp(
            "#where [`\"]" . self::UNIQUE_FIELD . "[`\"] =#i",
            $this->repository->query()->toSql(),
            "Query SQL should be altered by pushing Criteria once"
        );
    }

}
