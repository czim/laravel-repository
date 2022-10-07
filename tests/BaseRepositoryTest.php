<?php

declare(strict_types=1);

namespace Czim\Repository\Test;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Test\Helpers\TestSimpleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class BaseRepositoryTest extends TestCase
{
    protected const TABLE_NAME   = 'test_simple_models';
    protected const UNIQUE_FIELD = 'unique_field';
    protected const SECOND_FIELD = 'second_field';

    protected ?BaseRepositoryInterface $repository = null;


    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(Helpers\TestBaseRepository::class);
    }

    protected function seedDatabase(): void
    {
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
    public function it_handles_basic_retrieval_operations(): void
    {
        // all
        $result = $this->repository->all();
        static::assertInstanceOf(Collection::class, $result, 'Did not get Collection for all()');
        static::assertCount(3, $result, 'Did not get correct count for all()');

        // get an id that we can use find on
        $someId = $result->first()->id;
        static::assertNotEmpty($someId, "Did not get a valid Model's id from the all() result");

        // find
        static::assertInstanceOf(Model::class, $this->repository->find($someId), 'Did not get Model for find()');

        // count
        static::assertEquals(3, $this->repository->count(), 'Did not get correct result for count()');

        // first
        static::assertInstanceOf(Model::class, $this->repository->first(), 'Did not get Model for first() on all');

        // findBy
        static::assertInstanceOf(
            Model::class,
            $this->repository->findBy(self::UNIQUE_FIELD, '1337'),
            'Did not get Model for findBy() for unique field value'
        );

        // findAllBy
        static::assertCount(
            2,
            $this->repository->findAllBy('active', true),
            'Did not get correct count for result for findAllBy(active = true)'
        );

        // paginate
        static::assertCount(2, $this->repository->paginate(2), 'Did not get correct count for paginate()');

        // pluck
        $list = $this->repository->pluck(self::UNIQUE_FIELD);
        static::assertCount(3, $list, 'Did not get correct array count for lists()');
        static::assertContains('1337', $list, 'Did not get correct array content for lists()');
    }

    /**
     * @test
     */
    public function it_creates_a_new_instance_and_fills_attributes_with_data(): void
    {
        $attributes = [
            self::UNIQUE_FIELD => 'unique_field_value',
            self::SECOND_FIELD => 'second_field_value',
        ];

        $model = $this->repository->make($attributes);

        // Asserting that only the desired attributes got filled and are the same.
        static::assertEquals($attributes, $model->getDirty());

        // Asserting the the model had its attributes filled without being persisted.
        static::assertEquals(0, $this->repository->findWhere($attributes)->count());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_findorfail_does_not_find_anything(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findOrFail(895476);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_firstorfail_does_not_find_anything(): void
    {
        $this->expectException(ModelNotFoundException::class);

        // Make sure we won't find anything.
        $mockCriteria = $this->makeMockCriteria(
            'once',
            fn ($query) => $query->where('name', 'some name that certainly does not exist')
        );
        $this->repository->pushCriteria($mockCriteria);

        $this->repository->firstOrFail();
    }

    /**
     * Bosnadev's findWhere() method.
     *
     * @test
     */
    public function it_can_perform_a_findwhere_with_custom_parameters(): void
    {
        // Simple field/value combo's by key
        static::assertCount(
            1,
            $this->repository->findWhere([
                self::UNIQUE_FIELD => '1234567',
                self::SECOND_FIELD => '434',
            ]),
            'findWhere() with field/value combo failed (incorrect match count)'
        );

        // Arrays with field/value sets
        static::assertCount(
            1,
            $this->repository->findWhere([
                [self::UNIQUE_FIELD, '1234567'],
                [self::SECOND_FIELD, '434'],
            ]),
            'findWhere() with field/value sets failed (incorrect match count)'
        );

        // Arrays with field/operator/value sets
        static::assertCount(
            1,
            $this->repository->findWhere([
                [self::UNIQUE_FIELD, 'LIKE', '%234567'],
                [self::SECOND_FIELD, 'LIKE', '43%'],
            ]),
            'findWhere() with field/operator/value sets failed (incorrect match count)'
        );

        // Closure send directly to the model's where() method
        static::assertCount(
            1,
            $this->repository->findWhere([
                function ($query) {
                    return $query->where(self::UNIQUE_FIELD, 'LIKE', '%234567');
                },
            ]),
            'findWhere() with Closure callback failed (incorrect match count)'
        );
    }

    /**
     * @test
     */
    public function it_can_perform_find_and_all_lookups_with_a_callback_for_custom_queries(): void
    {
        // allCallback
        $result = $this->repository->allCallback(function ($query) {
            return $query->where(self::UNIQUE_FIELD, '1337');
        });
        static::assertCount(1, $result, 'Wrong count for allCallback()');


        // findCallback
        $result = $this->repository->findCallback(function ($query) {
            return $query->where(self::UNIQUE_FIELD, '1337');
        });
        static::assertEquals('1337', $result->{self::UNIQUE_FIELD}, 'Wrong result for findCallback()');
    }

    /**
     * @test
     */
    public function it_throw_an_exception_if_the_callback_for_custom_queries_is_incorrect(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repository->allCallback(function () {
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
    public function it_handles_basic_manipulation_operations(): void
    {
        // Update existing
        $someId = $this->repository->findBy(self::UNIQUE_FIELD, '999')->id;
        static::assertNotEmpty($someId, "Did not get a valid Model's id from the findBy(unique_field) result");
        $this->repository->update(['name' => 'changed it!'], $someId);
        static::assertEquals(
            'changed it!',
            $this->repository->findBy(self::UNIQUE_FIELD, '999')->name,
            'Change did not apply after update()'
        );

        // Create new
        $model = $this->repository->create([
            self::UNIQUE_FIELD => '313',
            'name'             => 'New Model',
        ]);
        static::assertInstanceOf(Model::class, $model, 'Create() response is not a Model');
        static::assertNotEmpty($model->id, 'Model does not have an id (likely story)');
        static::assertDatabaseHas(static::TABLE_NAME, ['id'   => $model->id, self::UNIQUE_FIELD => '313',
                                                      'name' => 'New Model',
        ]);
        static::assertEquals(4, $this->repository->count(), 'Total count after creating new does not match');

        // Delete
        static::assertEquals(1, $this->repository->delete($model->id), 'Delete() call did not return succesful count');
        static::assertEquals(3, $this->repository->count(), 'Total count after deleting does not match');
        static::assertDatabaseMissing(static::TABLE_NAME, ['id' => $model->id]);
        unset($model);
    }

    /**
     * @test
     */
    public function it_fills_a_retrieved_model_attributes_without_persisting_it(): void
    {
        $persistedModel = $this->repository->all()->first();

        $attributes = [
            self::UNIQUE_FIELD => 'unique_field_value',
            self::SECOND_FIELD => 'second_field_value',
        ];

        $filledModel = $this->repository->fill($attributes, $persistedModel->id);

        static::assertEquals($filledModel->getDirty(), $attributes);
        static::assertDatabaseMissing(static::TABLE_NAME, $attributes);
    }


    // --------------------------------------------
    //      Criteria
    // --------------------------------------------

    /**
     * @test
     */
    public function it_returns_and_can_restore_default_criteria(): void
    {
        static::assertTrue($this->repository->defaultCriteria()->isEmpty(), 'Defaultcriteria is not empty');

        $this->repository->pushCriteria($this->makeMockCriteria('never'));
        static::assertCount(
            1,
            $this->repository->getCriteria(),
            'getCriteria() count incorrect after pushing new Criteria'
        );

        $this->repository->restoreDefaultCriteria();
        static::assertTrue(
            $this->repository->getCriteria()->isEmpty(),
            'getCriteria() not empty after restoring default Criteria()'
        );
    }

    /**
     * @test
     * @depends it_handles_basic_retrieval_operations
     */
    public function it_takes_criteria_and_handles_basic_criteria_manipulation(): void
    {
        // Clear all criteria, see if none are applied.
        $this->repository->clearCriteria();
        static::assertTrue(
            $this->repository->getCriteria()->isEmpty(),
            'getCriteria() not empty after clearCriteria()'
        );
        static::assertMatchesRegularExpression(
            "#^select \* from [`\"]" . static::TABLE_NAME . '[`\"]$#i',
            $this->repository->query()->toSql(),
            'Query SQL should be totally basic after clearCriteria()'
        );


        // Add new criteria, see if it is applied.
        $criteria = $this->makeMockCriteria('twice', fn ($query) => $query->where(self::UNIQUE_FIELD, '1337'));
        $this->repository->pushCriteria($criteria, 'TemporaryCriteria');
        static::assertCount(
            1,
            $this->repository->getCriteria(),
            'getCriteria() count incorrect after pushing new Criteria'
        );

        static::assertMatchesRegularExpression(
            '#where [`"]' . self::UNIQUE_FIELD . '[`"] =#i',
            $this->repository->query()->toSql(),
            'Query SQL should be altered by pushing Criteria'
        );

        // Set repository to ignore criteria, see if they do not get applied.
        $this->repository->ignoreCriteria();

        static::assertDoesNotMatchRegularExpression(
            '#where [`\"]' . self::UNIQUE_FIELD . '[`\"] =#i',
            $this->repository->query()->toSql(),
            'Query SQL should be altered by pushing Criteria'
        );

        $this->repository->ignoreCriteria(false);


        // Remove criteria once, see if it is not applied.
        $this->repository->removeCriteriaOnce('TemporaryCriteria');
        static::assertCount(
            1,
            $this->repository->getCriteria(),
            'getCriteria() should still have a count of one if only removing temporarily'
        );
        static::assertMatchesRegularExpression(
            "#^select \* from [`\"]" . static::TABLE_NAME . '[`\"]$#i',
            $this->repository->query()->toSql(),
            'Query SQL should be totally basic while removing Criteria once'
        );
        static::assertMatchesRegularExpression(
            '#where [`\"]' . self::UNIQUE_FIELD . '[`\"] =#i',
            $this->repository->query()->toSql(),
            'Query SQL should be altered again on next call after removing Criteria once'
        );


        // override criteria once, see if it is overridden succesfully and not called
        $secondCriteria = $this->makeMockCriteria('once', fn ($query) => $query->where(self::SECOND_FIELD, '12345'));
        $this->repository->pushCriteriaOnce($secondCriteria, 'TemporaryCriteria');
        $sql = $this->repository->query()->toSql();
        static::assertDoesNotMatchRegularExpression(
            '#where [`\"]' . self::UNIQUE_FIELD . '[`\"] =#i',
            $sql,
            'Query SQL should not be built using first TemporaryCriteria'
        );
        static::assertMatchesRegularExpression(
            '#where [`\"]' . self::SECOND_FIELD . '[`\"] =#i',
            $sql,
            'Query SQL should be built using the overriding Criteria'
        );


        // remove specific criteria, see if it is not applied
        $this->repository->removeCriteria('TemporaryCriteria');
        static::assertTrue(
            $this->repository->getCriteria()->isEmpty(),
            'getCriteria() not empty after removing Criteria'
        );
        static::assertMatchesRegularExpression(
            "#^select \* from [`\"]" . static::TABLE_NAME . '[`\"]$#i',
            $this->repository->query()->toSql(),
            'Query SQL should be totally basic after removing Criteria'
        );


        // override criteria once, see if it is changed
        $criteria = $this->makeMockCriteria('once', fn ($query) => $query->where(self::UNIQUE_FIELD, '1337'));
        $this->repository->pushCriteriaOnce($criteria);
        static::assertTrue(
            $this->repository->getCriteria()->isEmpty(),
            'getCriteria() not empty with only once Criteria pushed'
        );
        static::assertMatchesRegularExpression(
            '#where [`\"]' . self::UNIQUE_FIELD . '[`\"] =#i',
            $this->repository->query()->toSql(),
            'Query SQL should be altered by pushing Criteria once'
        );
    }
}
