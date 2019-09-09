<?php
namespace Czim\Repository\Test;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Contracts\ExtendedRepositoryInterface;
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
    const TABLE_NAME   = 'test_simple_models';
    const UNIQUE_FIELD = 'unique_field';
    const SECOND_FIELD = 'second_field';

    /**
     * @var BaseRepositoryInterface|ExtendedRepositoryInterface
     */
    protected $repository;


    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(Helpers\TestExtendedRepository::class);

        $this->repository->maintenance();
    }

    protected function seedDatabase()
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
    function field_is_value_criteria_works()
    {
        $this->repository->pushCriteria(new FieldIsValue('name', 'special name'));

        $this->assertCount(1, $this->repository->all(), "FieldIsValue Criteria doesn't work");
    }

    /**
     * @test
     */
    function has_criteria_works()
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
    function is_active_criteria_works()
    {
        $this->repository->pushCriteria(new IsActive('active'));

        $this->assertCount(2, $this->repository->all(), "IsActive Criteria doesn't work");
    }

    /**
     * @test
     */
    function order_by_criteria_works()
    {
        $this->repository->pushCriteria(new OrderBy('position', 'desc'));

        $this->assertEquals([3, 2, 1], $this->repository->lists('position'), "OrderBy Criteria doesn't work");
    }

    /**
     * @test
     */
    function scope_criteria_works()
    {
        $this->repository->pushCriteria(new Scope('testing'), CriteriaKey::SCOPE);

        $this->assertCount(2, $this->repository->all(), "Scope Criteria without parameters doesn't work");

        $this->repository->pushCriteria(new Scope('moreTesting', [ self::SECOND_FIELD, '434' ]), CriteriaKey::SCOPE);

        $this->assertCount(1, $this->repository->all(), "Scope Criteria with parameter doesn't work");
    }

    /**
     * @test
     */
    function scopes_criteria_works()
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
    function where_has_criteria_works()
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
    function with_relations_criteria_works()
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
    function take_criteria_works()
    {
        $this->repository->pushCriteria(new Take(2));

        $this->assertCount(2, $this->repository->all(), "Take Criteria doesn't work");
    }

}
