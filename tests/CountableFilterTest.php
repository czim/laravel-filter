<?php
namespace Czim\Filter\Test;

use Czim\Filter\Contracts\CountableFilterInterface;
use Czim\Filter\CountableResults;
use Czim\Filter\Test\Helpers\TestCountableFilter;
use Czim\Filter\Test\Helpers\TestRelatedModel;
use Czim\Filter\Test\Helpers\TestSimpleModel;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CountableFilterTest extends TestCase
{
    const TABLE_NAME   = 'test_simple_models';
    const UNIQUE_FIELD = 'unique_field';
    const SECOND_FIELD = 'second_field';


    public function setUp()
    {
        parent::setUp();
    }

    protected function seedDatabase()
    {
        TestSimpleModel::create([
            'unique_field'          => '11',
            'second_field'          => null,
            'name'                  => 'simple name',
            'test_related_model_id' => 1,
            'position'              => 0,
            'active'                => true,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '123',
            'second_field'          => 'random string',
            'name'                  => 'random name',
            'test_related_model_id' => 2,
            'position'              => 1,
            'active'                => true,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '1337',
            'second_field'          => 'some more',
            'name'                  => 'special name',
            'test_related_model_id' => 3,
            'position'              => 14,
            'active'                => true,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '1980',
            'second_field'          => 'yet more fun',
            'name'                  => 'another name',
            'test_related_model_id' => 1,
            'position'              => 14,
            'active'                => true,
        ]);

        TestRelatedModel::create([
            'name'                 => 'related A',
            'some_property'        => 'super',
            'test_simple_model_id' => 3,
            'active'               => true,
        ]);

        TestRelatedModel::create([
            'name'                 => 'related B',
            'some_property'        => 'generic',
            'test_simple_model_id' => 3,
            'active'               => true,
        ]);

        TestRelatedModel::create([
            'name'                 => 'related C',
            'some_property'        => 'mild',
            'test_simple_model_id' => 2,
            'active'               => true,
        ]);
    }


    // --------------------------------------------
    //      Instantiation / Init
    // --------------------------------------------

    /**
     * @test
     */
    function it_can_be_instantiated()
    {
        $this->assertInstanceOf(
            CountableFilterInterface::class,
            new TestCountableFilter([])
        );

        $this->assertInstanceOf(
            CountableFilterInterface::class,
            new TestCountableFilter([ 'name' => 'test' ])
        );
    }


    /**
     * @test
     */
    function it_returns_list_of_countable_parameter_names()
    {
        $filter = new TestCountableFilter([]);

        $this->assertCount(
            2,
            $filter->getCountables(),
            "Wrong count for getCountables()"
        );
    }


    // --------------------------------------------
    //      getCounts
    // --------------------------------------------

    /**
     * @test
     */
    function it_returns_correct_counts()
    {
        $filter = new TestCountableFilter([]);

        $counts = $filter->getCounts();

        $this->assertInstanceOf(CountableResults::class, $counts, "getCounts() result is of wrong type");
        $this->assertCount(2, $counts, "getCounts() results should have 2 items");

        // position 14 appears twice, 0 and 1 once
        $this->assertEquals(
            [ 0 => 1,  1 => 1, 14 => 2 ],
            $counts->get('position')->toArray(),
            "getCounts() first (distinct value) results incorrect"
        );

        // related model id 1 appears twice, the rest once
        $this->assertEquals(
            [ 1 => 2,  2 => 1, 3 => 1 ],
            $counts->get('relateds')->toArray(),
            "getCounts() first (belongsto) results incorrect"
        );
    }

}
