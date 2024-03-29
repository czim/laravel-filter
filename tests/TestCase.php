<?php

declare(strict_types=1);

namespace Czim\Filter\Test;

use Czim\Filter\Test\Helpers\TranslatableConfig;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected const TABLE_NAME_SIMPLE       = 'test_simple_models';
    protected const TABLE_NAME_TRANSLATIONS = 'test_simple_model_translations';
    protected const TABLE_NAME_RELATED      = 'test_related_models';

    protected ?DatabaseManager $db;

    /**
     * {@inheritDoc}
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('translatable', (new TranslatableConfig())->getConfig());
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();
        $this->seedDatabase();
    }


    protected function migrateDatabase(): void
    {
        // Model we can test anything but translations with.
        Schema::create(self::TABLE_NAME_SIMPLE, function ($table): void {
            $table->increments('id');
            $table->string('unique_field', 20);
            $table->integer('second_field')->unsigned()->nullable();
            $table->string('name', 255)->nullable();
            $table->integer('test_related_model_id');
            $table->integer('position')->nullable();
            $table->boolean('active')->nullable()->default(false);
            $table->timestamps();
        });

        // Model we can also test translations with.
        Schema::create(self::TABLE_NAME_RELATED, function ($table): void {
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->string('some_property', 20)->nullable();
            $table->integer('test_simple_model_id')->nullable();
            $table->integer('position')->nullable();
            $table->boolean('active')->nullable()->default(false);
            $table->timestamps();
        });

        Schema::create(self::TABLE_NAME_TRANSLATIONS, function ($table): void {
            $table->increments('id');
            $table->integer('test_simple_model_id')->unsigned();
            $table->string('locale', 12);
            $table->string('translated_string', 255);
            $table->timestamps();
        });
    }

    protected function seedDatabase(): void
    {
        // noop
    }
}
