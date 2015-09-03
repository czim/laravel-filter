<?php
namespace Czim\Filter\Test;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Test\Helpers\TestFilter;
use Czim\Filter\Test\Helpers\TestFilterData;
use Czim\Filter\Test\Helpers\TestRelatedModel;
use Czim\Filter\Test\Helpers\TestSimpleModel;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FilterTest extends TestCase
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
            'active'                => true,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '123',
            'second_field'          => 'random string',
            'name'                  => 'random name',
            'test_related_model_id' => 2,
            'active'                => false,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '1337',
            'second_field'          => 'some more',
            'name'                  => 'special name',
            'test_related_model_id' => 3,
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
    function it_can_be_instantiated_with_array_data()
    {
        $this->assertInstanceOf(
            FilterInterface::class,
            new TestFilter([
                'name'          => 'some name',
                'relateds'      => [ 1, 2, 3 ],
                'position'      => 20,
                'with_inactive' => false,
            ])
        );
    }

    /**
     * @test
     * @expectedException \Czim\Filter\Exceptions\FilterDataValidationFailedException
     */
    function it_throws_an_exception_if_invalid_data_is_passed_in()
    {

        // see if we get the messages correctly
        try {
            new TestFilter([
                'name'          => 'some name',
                'relateds'      => 'string which should be an array',
                'position'      => 'string which should be an integer',
                'with_inactive' => 'not even a boolean here',
            ]);
        } catch (\Czim\Filter\Exceptions\FilterDataValidationFailedException $e) {

            $messages = $e->getMessages();

            $this->assertInstanceOf(MessageBag::class, $messages, "Exception getMessages is not a MessageBag");
            $this->assertCount(3, $messages, "Exception getMessages should have 3 messages");
        }

        // throw the exception, but don't catch it this time
        new TestFilter([
            'name'          => 'some name',
            'relateds'      => 'string which should be an array',
            'position'      => 'string which should be an integer',
            'with_inactive' => 'not even a boolean here',
        ]);
    }

    /**
     * @test
     */
    function it_can_be_instantiated_with_a_filter_data_object()
    {
        $filterData = new TestFilterData([
            'name'          => 'some name',
            'relateds'      => [ 1, 2, 3 ],
            'position'      => 20,
            'with_inactive' => false,
        ]);


        $this->assertInstanceOf(
            FilterInterface::class,
            new TestFilter($filterData)
        );
    }


    // --------------------------------------------
    //      Getters/Setters and such
    // --------------------------------------------

    /**
     * @test
     */
    function it_can_get_and_set_filter_data_objects()
    {
        $filter = new TestFilter([ 'name' => 'first name filter' ]);

        $this->assertEquals(
            'first name filter',
            $filter->getFilterData()->toArray()['name'],
            "Incorrect name for first set of filterdata"
        );

        $filterData = new TestFilterData([
            'name'          => 'some name',
            'relateds'      => [ 1, 2, 3 ],
            'position'      => 20,
            'with_inactive' => false,
        ]);

        $filter->setFilterData($filterData);

        $this->assertEquals(
            'some name',
            $filter->getFilterData()->toArray()['name'],
            "Filter data did not change after setFilterData()"
        );
    }

    /**
     * @test
     */
    function it_can_get_and_set_global_settings()
    {
        $filter = new TestFilter([ 'name' => 'first name filter' ]);

        $this->assertEmpty($filter->setting('does_not_exist'), "Setting that was never set should be empty");

        $filter->setSetting('some_setting', 'some value');

        $this->assertEquals(
            'some value',
            $filter->setting('some_setting'),
            "Setting that was set did not have correct value"
        );
    }

    /**
     * @test
     */
    function it_can_set_global_settings_by_way_of_filter_parameter_strategy()
    {
        $filter = new TestFilter([ 'global_setting' => 'SWEET SETTING VALUE' ]);

        // only happens when it applies the setting!
        // this should especially NOT throw the exception for 'no fallback'.
        $filter->apply(TestSimpleModel::query());

        $this->assertEquals(
            'SWEET SETTING VALUE',
            $filter->setting('global_setting'),
            "Setting that was set as filter parameter strategy did not have correct value"
        );
    }

    // --------------------------------------------
    //      Exceptions for strategies
    // --------------------------------------------

    /**
     * @test
     * @expectedException \Czim\Filter\Exceptions\FilterParameterUnhandledException
     */
    function it_throws_an_exception_if_no_strategy_was_defined_for_a_parameter()
    {
        (new TestFilter([ 'no_strategy_set_no_fallback' => 'something to activate it' ]))
            ->apply(TestSimpleModel::query());
    }

    /**
     * @test
     * @expectedException \Czim\Filter\Exceptions\ParameterStrategyInvalidException
     * @expectedExceptionMessageRegExp #uninstantiable_string_that_is_not_a_parameter_filter#i
     */
    function it_throws_an_exception_if_a_strategy_string_is_not_instantiable()
    {
        (new TestFilter([ 'invalid_strategy_string' => 'ignored' ]))
            ->apply(TestSimpleModel::query());
    }

    /**
     * @test
     * @expectedException \Czim\Filter\Exceptions\ParameterStrategyInvalidException
     */
    function it_throws_an_exception_if_a_strategy_value_is_of_wrong_type()
    {
        (new TestFilter([ 'invalid_strategy_general' => 'ignored' ]))
            ->apply(TestSimpleModel::query());
    }

    /**
     * @test
     * @expectedException \Czim\Filter\Exceptions\ParameterStrategyInvalidException
     * @expectedExceptionMessageRegExp #is not a?\s*ParameterFilter#i
     */
    function it_throws_an_exception_if_an_instantiated_strategy_string_does_not_implement_parameterfilterinterface()
    {
        (new TestFilter([ 'invalid_strategy_interface' => 'ignored' ]))
            ->apply(TestSimpleModel::query());
    }


    // --------------------------------------------
    //      Applying parameters
    // --------------------------------------------

    /**
     * @test
     */
    function it_applies_parameters_to_a_query()
    {
        // uses the defaults even if no parameters set
        $result = (new TestFilter([]))->apply(TestSimpleModel::query())->get();

        $this->assertCount(2, $result, "Count for no parameters set result incorrect (should be 2 with 'active' = 1)");

        // simple single filter
        $result = (new TestFilter([ 'name' => 'special' ]))->apply(TestSimpleModel::query())->get();

        $this->assertCount(1, $result, "Count for single filter parameter (loosy string, strategy parameterfilter) incorrect");
        $this->assertEquals('1337', $result->first()->{self::UNIQUE_FIELD}, "Result incorrect for single filter parameter");

        // double filter, with relation id parameterfilter
        $result = (new TestFilter([
            'name'          => 'name',
            'relateds'      => [1, 2],
            'with_inactive' => true,
        ]))->apply(TestSimpleModel::query())->get();

        $this->assertCount(2, $result, "Count for multiple filter parameters (loosy string and relation ids, and inactive) incorrect");
    }

    /**
     * @test
     */
    function it_applies_parameters_by_strategy_of_instantiated_parameter_filter()
    {
        $result = (new TestFilter([ 'parameter_filter_instance' => 'special name' ]))->apply(TestSimpleModel::query())->get();

        $this->assertCount(1, $result, "Count for single filter parameter incorrect (exact string, strategy parameterfilter)");
        $this->assertEquals('1337', $result->first()->{self::UNIQUE_FIELD}, "Result incorrect for single filter parameter (exact string, strategy parameterfilter)");
    }

    /**
     * @test
     */
    function it_applies_parameters_by_strategy_of_instantiable_parameter_filter_class_string()
    {
        $result = (new TestFilter([ 'parameter_filter_string' => 'ignored, hardcoded test filter' ]))->apply(TestSimpleModel::query())->get();

        $this->assertCount(1, $result, "Count for single filter parameter incorrect (strategy parameterfilter by string)");
        $this->assertEquals('1337', $result->first()->{self::UNIQUE_FIELD}, "Result incorrect for single filter parameter (strategy parameterfilter by string)");
    }

    /**
     * @test
     */
    function it_applies_parameters_by_strategy_of_closure()
    {
        // closure as anonymous function
        $result = (new TestFilter([ 'closure_strategy' => [ 'special name', 3 ] ]))->apply(TestSimpleModel::query())->get();

        $this->assertCount(1, $result, "Count for single filter parameter incorrect (strategy closure with parameters)");
        $this->assertEquals('1337', $result->first()->{self::UNIQUE_FIELD}, "Result incorrect for single filter parameter (strategy closure with parameters)");

        // closure as [ object, method ] array
        $result = (new TestFilter([ 'closure_strategy_array' => [ 'special name', 3 ] ]))->apply(TestSimpleModel::query())->get();

        $this->assertCount(1, $result, "Count for single filter parameter incorrect (strategy closure with parameters, array syntax)");
        $this->assertEquals('1337', $result->first()->{self::UNIQUE_FIELD}, "Result incorrect for single filter parameter (strategy closure with parameters, array syntax)");
    }


    // --------------------------------------------
    //      Joins handling and ignoring params
    // --------------------------------------------

    // test adding joins, whether no duplicates are added
    // test whether the (un)ignoreParameter methods work
}
