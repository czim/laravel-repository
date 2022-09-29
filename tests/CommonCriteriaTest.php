<?php

declare(strict_types=1);

namespace Czim\Repository\Test;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Criteria\Common\FieldIsValue;
use Czim\Repository\Criteria\Common\Has;
use Czim\Repository\Criteria\Common\IsActive;
use Czim\Repository\Criteria\Common\OrderBy;
use Czim\Repository\Criteria\Common\Scope;
use Czim\Repository\Criteria\Common\Scopes;
use Czim\Repository\Criteria\Common\Take;
use Czim\Repository\Criteria\Common\WhereHas;
use Czim\Repository\Criteria\Common\WithRelations;
use Czim\Repository\Enums\CriteriaKey;
use Czim\Repository\Test\Helpers\TestExtendedModel;

class CommonCriteriaTest extends TestCase
{
    protected const TABLE_NAME   = 'test_simple_models';
    protected const UNIQUE_FIELD = 'unique_field';
    protected const SECOND_FIELD = 'second_field';

    protected ?BaseRepositoryInterface $repository = null;


    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(Helpers\TestExtendedRepository::class);

        $this->repository->maintenance();
    }

    protected function seedDatabase(): void
    {
        TestExtendedModel::create([
            'unique_field' => '999',
            'second_field' => null,
            'name'         => 'unchanged',
            'active'       => true,
        ]);

        TestExtendedModel::create([
            'unique_field' => '1234567',
            'second_field' => '434',
            'name'         => 'random name',
            'active'       => false,
        ]);

        $testModel = TestExtendedModel::create([
            'unique_field' => '1337',
            'second_field' => '12345',
            'name'         => 'special name',
            'active'       => true,
        ]);

        // set some translations
        $testModel->translateOrNew('nl')->translated_string = 'vertaalde_attribuutwaarde hoepla';
        $testModel->translateOrNew('en')->translated_string = 'translated_attribute_value hoopla';
        $testModel->save();
    }


    /**
     * @test
     */
    public function field_is_value_criteria_works(): void
    {
        $this->repository->pushCriteria(new FieldIsValue('name', 'special name'));

        $this->assertCount(1, $this->repository->all(), "FieldIsValue Criteria doesn't work");
    }

    /**
     * @test
     */
    public function has_criteria_works(): void
    {
        $this->repository->pushCriteria(new Has('translations', '>', 1));

        $this->assertCount(1, $this->repository->all(), "Has Criteria simple use fails");

        $this->repository->pushCriteria(new Has('translations', '=', 1, 'and', function($query) {
            return $query->where('translated_string', 'vertaalde_attribuutwaarde hoepla');
        }));

        $this->assertCount(1, $this->repository->all(), "Has Criteria use with callback fails");
    }

    /**
     * @test
     */
    public function is_active_criteria_works(): void
    {
        $this->repository->pushCriteria(new IsActive('active'));

        $this->assertCount(2, $this->repository->all(), "IsActive Criteria doesn't work");
    }

    /**
     * @test
     */
    public function order_by_criteria_works(): void
    {
        $this->repository->pushCriteria(new OrderBy('position', 'desc'));

        $this->assertEquals([3, 2, 1], $this->repository->pluck('position')->all(), "OrderBy Criteria doesn't work");
    }

    /**
     * @test
     */
    public function scope_criteria_works(): void
    {
        $this->repository->pushCriteria(new Scope('testing'), CriteriaKey::SCOPE);

        $this->assertCount(2, $this->repository->all(), "Scope Criteria without parameters doesn't work");

        $this->repository->pushCriteria(new Scope('moreTesting', [ self::SECOND_FIELD, '434' ]), CriteriaKey::SCOPE);

        $this->assertCount(1, $this->repository->all(), "Scope Criteria with parameter doesn't work");
    }

    /**
     * @test
     */
    public function scopes_criteria_works(): void
    {
        $this->repository->pushCriteria(new Scopes([
            'testing',
            'moreTesting' => [ 'active', false ],
        ]), CriteriaKey::SCOPE);

        $this->assertCount(1, $this->repository->all(), "Multiple Scopes Criteria doesn't work (value & key => value)");

        $this->repository->pushCriteria(new Scopes([
            [ 'testing' ],
            [ 'moreTesting', [ 'active', false ] ],
        ]), CriteriaKey::SCOPE);

        $this->assertCount(1, $this->repository->all(), "Multiple Scopes Criteria doesn't work (array sets, no keys)");
    }

    /**
     * @test
     */
    public function where_has_criteria_works(): void
    {
        $this->repository->pushCriteria(new WhereHas('translations', function($query) {
            return $query->where('translated_string', 'vertaalde_attribuutwaarde hoepla');
        }));

        $result = $this->repository->all();
        $this->assertCount(1, $result, "WhereHas Criteria doesn't work (wrong count)");
        $this->assertEquals('1337', $result->first()->{self::UNIQUE_FIELD}, "WhereHas Criteria doesn't work (wrong model)");
    }

    /**
     * @test
     */
   public function with_relations_criteria_works(): void
    {
        $this->assertEmpty(
            $this->repository->findBy(self::UNIQUE_FIELD, '1337')->getRelations(),
            "Model already includes translations relation without WithRelations Criteria"
        );

        $this->repository->pushCriteria(new WithRelations(['translations']));

        $this->assertNotEmpty(
            $this->repository->findBy(self::UNIQUE_FIELD, '1337')->getRelations(),
            "Model does not include translations relation with WithRelations Criteria"
        );
    }

    /**
     * @test
     */
    public function take_criteria_works(): void
    {
        $this->repository->pushCriteria(new Take(2));

        $this->assertCount(2, $this->repository->all(), "Take Criteria doesn't work");
    }

}
