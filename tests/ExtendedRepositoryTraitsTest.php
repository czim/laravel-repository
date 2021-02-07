<?php
namespace Czim\Repository\Test;

use Czim\Repository\ExtendedRepository;
use Czim\Repository\Test\Helpers\TestExtendedModel;

class ExtendedRepositoryTraitsTest extends TestCase
{
    const TABLE_NAME       = 'test_extended_models';
    const UNIQUE_FIELD     = 'unique_field';
    const SECOND_FIELD     = 'second_field';
    const TRANSLATED_FIELD = 'translated_string';

    /**
     * @var ExtendedRepository
     */
    protected $repository;


    public function setUp(): void
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

        $testModel = TestExtendedModel::create([
            'unique_field' => '1337',
            'second_field' => '12345',
            'name'         => 'special name',
            'active'       => true,
            'hidden'       => 'where has it gone?',
        ]);

        // set some translations
        $testModel->translateOrNew('nl')->translated_string = 'vertaalde_attribuutwaarde hoepla';
        $testModel->translateOrNew('en')->translated_string = 'translated_attribute_value hoopla';
        $testModel->save();
    }


    // --------------------------------------------
    //      Translatable
    // --------------------------------------------

    /**
     * @test
     */
    public function it_finds_records_by_translated_attribute_value()
    {
        // finds by translation exact
        $this->assertInstanceOf(
            TestExtendedModel::class,
            $this->repository->findByTranslation(self::TRANSLATED_FIELD, 'vertaalde_attribuutwaarde hoepla', 'nl'),
            "Did not find exact match for find"
        );
        $this->assertNotInstanceOf(
            TestExtendedModel::class,
            $this->repository->findByTranslation(self::TRANSLATED_FIELD, 'vertaalde_attribuutwaarde hoepla', 'en'),
            "Should not have found match for different locale"
        );
        // finds by translation LIKE
        $this->assertInstanceOf(
            TestExtendedModel::class,
            $this->repository->findByTranslation(self::TRANSLATED_FIELD, '%attribuutwaarde hoe%', 'nl', false),
            "Did not find loosy match for find"
        );

        // finds ALL by translation exact
        $this->assertCount(
            1,
            $this->repository->findAllByTranslation(self::TRANSLATED_FIELD, 'vertaalde_attribuutwaarde hoepla', 'nl'),
            "Incorrect count with exact match for all"
        );

        // finds ALL by translation LIKE
        // also check if we don't get duplicates for multiple hits
        $this->assertCount(
            1,
            $this->repository->findAllByTranslation(self::TRANSLATED_FIELD, '%vertaalde_attribuutwaarde%', 'nl', false),
            "Incorrect count with loosy match for all"
        );
    }


    // --------------------------------------------
    //      Compatibility with Listify
    // --------------------------------------------

    /**
     * @test
     */
    public function it_creates_new_records_with_position_handled_by_listify()
    {
        // the Supplier model must have Listify set for this
        $this->repository->maintenance();

        // get the highest position value in the database
        $highestPosition = $this->app['db']->table(static::TABLE_NAME)->max('position');
        $this->assertGreaterThan(0, $highestPosition, "Position value before testing not usable. Is Listify working/used?");

        $newModel = $this->repository->create([
            'unique_field' => 'NEWPOSITION',
            'name'         => 'TestNew',
        ]);

        $this->assertEquals($highestPosition + 1, $newModel->position, "New position should be highest position before + 1");
    }

    /**
     * @test
     * @todo rewrite this so that it uses listify method instead
     * @todo and add other useful listify methods?
     */
    public function it_updates_the_list_position_of_a_record()
    {
        $this->repository->maintenance();

        // check starting situation
        $changeModel = $this->repository->findBy(self::UNIQUE_FIELD, '1337');
        $this->assertEquals(1, $this->repository->findBy(self::UNIQUE_FIELD, '999')->position, "Starting position for record (999) is incorrect");
        $this->assertEquals(3, $changeModel->position, "Starting position for record (1337) is incorrect");

        // update the position of the last added entry
        $this->repository->updatePosition($changeModel->id, 1);

        // check final positions after update
        $this->assertEquals(2, $this->repository->findBy(self::UNIQUE_FIELD, '999')->position, "Final position for record (999) is incorrect");
        $this->assertEquals(1, $this->repository->findBy(self::UNIQUE_FIELD, '1337')->position, "Final position for moved record (1337) is incorrect");
    }
}
