<?php
namespace Czim\Repository\Test;

use Czim\Repository\ExtendedPostProcessingRepository;
use Czim\Repository\Test\Helpers\TestExtendedModel;

class ExtendedPostProcessingRepositoryTest extends TestCase
{
    const TABLE_NAME   = 'test_extended_models';
    const UNIQUE_FIELD = 'unique_field';
    const SECOND_FIELD = 'second_field';
    const HIDDEN_FIELD = 'hidden';

    /**
     * @var ExtendedPostProcessingRepository
     */
    protected $repository;


    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(Helpers\TestExtendedPostProcessingRepository::class);
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
    //      Hiding attributes
    // --------------------------------------------

    /**
     * @test
     */
    function it_can_hide_and_unhide_attributes_through_postprocessing()
    {
        $this->repository->maintenance();

        $model = $this->repository->findBy(self::UNIQUE_FIELD, '1337');
        $modelId = $model->id;
        $this->assertNotEmpty($modelId, "Test Model not found");
        $this->assertArrayHasKey(
            'name',
            $model->toArray(),
            "Test Model did not have correct attribute visibility in default state"
        );
        $this->assertArrayNotHasKey(
            self::HIDDEN_FIELD,
            $model->toArray(),
            "Test Model did not have correct attribute visibility in default state"
        );

        // hide a normal attribute
        $this->repository->hideAttribute('name');
        $this->assertArrayNotHasKey(
            'name',
            $this->repository->find($modelId)->toArray(),
            "Visible attribute dit not become hidden");

        // reset
        $this->repository->resetHiddenAttributes();
        $this->assertArrayHasKey(
            'name',
            $this->repository->find($modelId)->toArray(),
            "Hidden attribute did not reset to visible"
        );

        // unhide a normally hidden attribute
        $this->repository->unhideAttribute(self::HIDDEN_FIELD);
        $this->assertArrayHasKey(
            self::HIDDEN_FIELD,
            $this->repository->find($modelId)->toArray(),
            "Hidden attribute did not become visible"
        );

        // reset again
        $this->repository->resetHiddenAttributes();
        $this->assertArrayNotHasKey(
            self::HIDDEN_FIELD,
            $this->repository->find($modelId)->toArray(),
            "Visible attribute did not reset to hidden"
        );
    }


    // --------------------------------------------
    //      PostProcessing
    // --------------------------------------------

    /**
     * @test
     */
    function it_takes_postprocessors_and_handles_basic_postprocessor_manipulation()
    {
        $this->repository->maintenance();

        // add a postprocessor
        $this->repository->pushPostProcessor(
            Helpers\TestPostProcessor::class,
            [ 'does_not_exist_on_model', 'postprocessor test!' ]
        );

        // see if it affects the output for find
        $this->assertEquals(
            'postprocessor test!',
            $this->repository->findBy(self::UNIQUE_FIELD, '1337')->does_not_exist_on_model,
            "Postprocessor did not affect findBy() result"
        );

        // see if it affects the output for all
        $this->assertEquals(
            'postprocessor test!',
            $this->repository->findAllBy(self::UNIQUE_FIELD, '1337')->first()->does_not_exist_on_model,
            "Postprocessor did not affect findAllBy() result"
        );

        // remove a postprocessor
        $this->repository->removePostProcessor(Helpers\TestPostProcessor::class);

        // see if the output is no longer affected
        $this->assertEmpty(
            $this->repository->findBy(self::UNIQUE_FIELD, '1337')->does_not_exist_on_model,
            "Postprocessor still affects result after removePostProcessor()"
        );
    }

}
