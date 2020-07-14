<?php
namespace Czim\Filter\Test;

use Czim\Filter\Contracts\CriteriaInterface;
use Czim\Filter\Test\Helpers\TranslatableConfig;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use PHPUnit_Framework_MockObject_MockObject;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    const TABLE_NAME_SIMPLE       = 'test_simple_models';
    const TABLE_NAME_TRANSLATIONS = 'test_simple_model_translations';
    const TABLE_NAME_RELATED      = 'test_related_models';

    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // set minutes for cache to live
        //$app['config']->set('cache.ttl', 60);

        $app['config']->set('translatable', (new TranslatableConfig)->getConfig());
    }


    public function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();
        $this->seedDatabase();
    }


    protected function migrateDatabase()
    {
        //$db = $this->app->make('db');

        // model we can test anything but translations with
        Schema::create(self::TABLE_NAME_SIMPLE, function($table) {
            $table->increments('id');
            $table->string('unique_field', 20);
            $table->integer('second_field')->unsigned()->nullable();
            $table->string('name', 255)->nullable();
            $table->integer('test_related_model_id');
            $table->integer('position')->nullable();
            $table->boolean('active')->nullable()->default(false);
            $table->timestamps();
        });

        // model we can also test translations with
        Schema::create(self::TABLE_NAME_RELATED, function($table) {
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->string('some_property', 20)->nullable();
            $table->integer('test_simple_model_id')->nullable();
            $table->integer('position')->nullable();
            $table->boolean('active')->nullable()->default(false);
            $table->timestamps();
        });

        Schema::create(self::TABLE_NAME_TRANSLATIONS, function($table) {
            $table->increments('id');
            $table->integer('test_simple_model_id')->unsigned();
            $table->string('locale', 12);
            $table->string('translated_string', 255);
            $table->timestamps();
        });

    }

    abstract protected function seedDatabase();


    ///**
    // * Makes a mock Criteria object for simple custom Criteria testing.
    // * If no callback is given, it will simply return the model/query unaltered
    // * (and have no effect).
    // *
    // * @param null     $expects
    // * @param string   $name
    // * @param Closure  $callback    the callback for the apply() method on the Criteria
    // * @return PHPUnit_Framework_MockObject_MockObject
    // */
    //protected function makeMockCriteria($expects = null, $name = 'MockCriteria', Closure $callback = null)
    //{
    //    $mock = $this->getMockBuilder(CriteriaInterface::class)
    //        ->disableOriginalConstructor()
    //        ->setMockClassName($name)
    //        ->getMock();
    //
    //    if (is_null($callback)) {
    //        $callback = function($model) { return $model; };
    //    }
    //
    //    if (is_null($expects)) {
    //
    //        $mock->method('apply')
    //            ->will($this->returnCallback( $callback ));
    //        return $mock;
    //    }
    //
    //
    //    $mock->expects($expects)
    //        ->method('apply')
    //        ->will($this->returnCallback( $callback ));
    //
    //    return $mock;
    //}

}
