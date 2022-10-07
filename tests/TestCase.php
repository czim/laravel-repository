<?php

declare(strict_types=1);

namespace Czim\Repository\Test;

use Closure;
use Czim\Repository\Contracts\CriteriaInterface;
use Czim\Repository\Test\Helpers\TranslatableConfig;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\MockInterface;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected const TABLE_NAME_SIMPLE                = 'test_simple_models';
    protected const TABLE_NAME_EXTENDED              = 'test_extended_models';
    protected const TABLE_NAME_EXTENDED_TRANSLATIONS = 'test_extended_model_translations';

    protected ?DatabaseManager $db = null;

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Set up default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set minutes for cache to live.
        $app['config']->set('cache.ttl', 60);

        $app['config']->set('translatable', (new TranslatableConfig())->getConfig());
    }


    public function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();
        $this->seedDatabase();
    }

    protected function migrateDatabase(): void
    {
        // model we can test anything but translations with
        Schema::create(self::TABLE_NAME_SIMPLE, function ($table) {
            $table->increments('id');
            $table->string('unique_field', 20);
            $table->integer('second_field')->unsigned()->nullable();
            $table->string('name', 255)->nullable();
            $table->integer('position')->nullable();
            $table->boolean('active')->nullable()->default(false);
            $table->timestamps();
        });

        // model we can also test translations with
        Schema::create(self::TABLE_NAME_EXTENDED, function ($table) {
            $table->increments('id');
            $table->string('unique_field', 20);
            $table->integer('second_field')->unsigned()->nullable();
            $table->string('name', 255)->nullable();
            $table->integer('position')->nullable();
            $table->boolean('active')->nullable()->default(false);
            $table->string('hidden', 30)->nullable();
            $table->timestamps();
        });

        Schema::create(self::TABLE_NAME_EXTENDED_TRANSLATIONS, function ($table) {
            $table->increments('id');
            $table->integer('test_extended_model_id')->unsigned();
            $table->string('locale', 12);
            $table->string('translated_string', 255);
            $table->timestamps();
        });
    }

    abstract protected function seedDatabase(): void;


    /**
     * Makes a mock Criteria object for simple custom Criteria testing.
     *
     * If no callback is given, it will simply return the model/query unaltered  (and have no effect).
     *
     * @param int|string|null $expects
     * @param Closure|null    $callback the callback for the apply() method on the Criteria
     * @return CriteriaInterface&MockInterface
     */
    protected function makeMockCriteria(
        string|int|null $expects = null,
        Closure $callback = null,
    ): MockInterface {
        $mock = Mockery::mock(CriteriaInterface::class);

        if ($callback === null) {
            $callback = fn ($model) => $model;
        }

        if (! $expects) {
            $mock->shouldReceive('apply')->andReturnUsing($callback);
            return $mock;
        }

        if (is_integer($expects)) {
            $mock->shouldReceive('apply')
                ->times($expects)
                ->andReturnUsing($callback);
        } else {
            $mock->shouldReceive('apply')
                ->{$expects}()
                ->andReturnUsing($callback);
        }

        return $mock;
    }
}
