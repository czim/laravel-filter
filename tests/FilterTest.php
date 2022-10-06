<?php

declare(strict_types=1);

namespace Czim\Filter\Test;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Exceptions\FilterParameterUnhandledException;
use Czim\Filter\Exceptions\ParameterStrategyInvalidException;
use Czim\Filter\Test\Helpers\Models\TestRelatedModel;
use Czim\Filter\Test\Helpers\Models\TestSimpleModel;
use Czim\Filter\Test\Helpers\TestFilter;
use Czim\Filter\Test\Helpers\TestFilterData;

class FilterTest extends TestCase
{
    protected const TABLE_NAME   = 'test_simple_models';
    protected const UNIQUE_FIELD = 'unique_field';
    protected const SECOND_FIELD = 'second_field';

    protected function seedDatabase(): void
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
    public function it_can_be_instantiated_with_array_data(): void
    {
        static::assertInstanceOf(
            FilterInterface::class,
            new TestFilter([
                'name'          => 'some name',
                'relateds'      => [1, 2, 3],
                'position'      => 20,
                'with_inactive' => false,
            ])
        );
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_a_filter_data_object(): void
    {
        $filterData = new TestFilterData([
            'name'          => 'some name',
            'relateds'      => [1, 2, 3],
            'position'      => 20,
            'with_inactive' => false,
        ]);


        static::assertInstanceOf(
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
    public function it_can_get_and_set_filter_data_objects(): void
    {
        $filter = new TestFilter(['name' => 'first name filter']);

        static::assertEquals(
            'first name filter',
            $filter->getFilterData()->toArray()['name'],
            "Incorrect name for first set of filterdata"
        );

        $filterData = new TestFilterData([
            'name'          => 'some name',
            'relateds'      => [1, 2, 3],
            'position'      => 20,
            'with_inactive' => false,
        ]);

        $filter->setFilterData($filterData);

        static::assertEquals(
            'some name',
            $filter->getFilterData()->toArray()['name'],
            "Filter data did not change after setFilterData()"
        );
    }

    /**
     * @test
     */
    public function it_can_get_and_set_global_settings(): void
    {
        $filter = new TestFilter(['name' => 'first name filter']);

        static::assertEmpty($filter->setting('does_not_exist'), "Setting that was never set should be empty");

        $filter->setSetting('some_setting', 'some value');

        static::assertEquals(
            'some value',
            $filter->setting('some_setting'),
            "Setting that was set did not have correct value"
        );

        // Returns null if never defined.
        static::assertNull($filter->setting('never_defined_this_key_at_all'), "Undefined settings should return null");
    }

    /**
     * @test
     */
    public function it_can_set_global_settings_by_way_of_filter_parameter_strategy(): void
    {
        $filter = new TestFilter(['global_setting' => 'SWEET SETTING VALUE']);

        // Only happens when it applies the setting!
        // This should especially NOT throw the exception for 'no fallback'.
        $filter->apply(TestSimpleModel::query());

        static::assertEquals(
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
     */
    public function it_throws_an_exception_if_no_strategy_was_defined_for_a_parameter(): void
    {
        $this->expectException(FilterParameterUnhandledException::class);

        (new TestFilter(['no_strategy_set_no_fallback' => 'something to activate it']))
            ->apply(TestSimpleModel::query());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_a_strategy_string_is_not_instantiable(): void
    {
        $this->expectException(ParameterStrategyInvalidException::class);
        $this->expectExceptionMessageMatches('#uninstantiable_string_that_is_not_a_parameter_filter#i');

        (new TestFilter(['invalid_strategy_string' => 'ignored']))
            ->apply(TestSimpleModel::query());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_a_strategy_value_is_of_wrong_type(): void
    {
        $this->expectException(ParameterStrategyInvalidException::class);

        (new TestFilter(['invalid_strategy_general' => 'ignored']))
            ->apply(TestSimpleModel::query());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_an_instantiated_strategy_string_does_not_implement_parameterfilterinterface(): void
    {
        $this->expectException(ParameterStrategyInvalidException::class);
        $this->expectExceptionMessageMatches('#is not a?\s*ParameterFilter#i');

        (new TestFilter(['invalid_strategy_interface' => 'ignored']))
            ->apply(TestSimpleModel::query());
    }


    // --------------------------------------------
    //      Applying parameters
    // --------------------------------------------

    /**
     * @test
     */
    public function it_applies_parameters_to_a_query(): void
    {
        // Uses the defaults even if no parameters set.
        $result = (new TestFilter([]))->apply(TestSimpleModel::query())->get();

        static::assertCount(2, $result, "Count for no parameters set result incorrect (should be 2 with 'active' = 1)");

        // simple single filter
        $result = (new TestFilter(['name' => 'special']))->apply(TestSimpleModel::query())->get();

        static::assertCount(
            1,
            $result,
            "Count for single filter parameter (loosy string, strategy parameterfilter) incorrect"
        );
        static::assertEquals(
            '1337',
            $result->first()->{self::UNIQUE_FIELD},
            "Result incorrect for single filter parameter"
        );

        // Double filter, with relation ID parameter filter.
        $result = (new TestFilter([
            'name'          => 'name',
            'relateds'      => [1, 2],
            'with_inactive' => true,
        ]))->apply(TestSimpleModel::query())->get();

        static::assertCount(
            2,
            $result,
            "Count for multiple filter parameters (loosy string and relation ids, and inactive) incorrect"
        );
    }

    /**
     * @test
     */
    public function it_applies_parameters_by_strategy_of_instantiated_parameter_filter(): void
    {
        $result = (new TestFilter(['parameter_filter_instance' => 'special name']))
            ->apply(TestSimpleModel::query())->get();

        static::assertCount(
            1,
            $result,
            "Count for single filter parameter incorrect (exact string, strategy parameterfilter)"
        );
        static::assertEquals(
            '1337',
            $result->first()->{self::UNIQUE_FIELD},
            "Result incorrect for single filter parameter (exact string, strategy parameterfilter)"
        );
    }

    /**
     * @test
     */
    public function it_applies_parameters_by_strategy_of_instantiable_parameter_filter_class_string(): void
    {
        $result = (new TestFilter(['parameter_filter_string' => 'ignored, hardcoded test filter']))
            ->apply(TestSimpleModel::query())->get();

        static::assertCount(
            1,
            $result,
            "Count for single filter parameter incorrect (strategy parameterfilter by string)"
        );
        static::assertEquals(
            '1337',
            $result->first()->{self::UNIQUE_FIELD},
            "Result incorrect for single filter parameter (strategy parameterfilter by string)"
        );
    }

    /**
     * @test
     */
    public function it_applies_parameters_by_strategy_of_closure(): void
    {
        // Closure as anonymous function.
        $result = (new TestFilter(['closure_strategy' => ['special name', 3]]))
            ->apply(TestSimpleModel::query())->get();

        static::assertCount(
            1,
            $result,
            "Count for single filter parameter incorrect (strategy closure with parameters)"
        );
        static::assertEquals(
            '1337',
            $result->first()->{self::UNIQUE_FIELD},
            "Result incorrect for single filter parameter (strategy closure with parameters)"
        );

        // Closure as [ object, method ] array.
        $result = (new TestFilter(['closure_strategy_array' => ['special name', 3]]))
            ->apply(TestSimpleModel::query())->get();

        static::assertCount(
            1,
            $result,
            "Count for single filter parameter incorrect (strategy closure with parameters, array syntax)"
        );
        static::assertEquals(
            '1337',
            $result->first()->{self::UNIQUE_FIELD},
            "Result incorrect for single filter parameter (strategy closure with parameters, array syntax)"
        );
    }


    // --------------------------------------------
    //      Joins handling
    // --------------------------------------------

    /**
     * @test
     */
    public function it_adds_joins_and_applies_them_after_all_filters(): void
    {
        // Add joins using addJoin method.
        $query = (new TestFilter([
            'adding_joins' => 'okay',
        ]))->apply(TestSimpleModel::query())->toSql();

        static::assertMatchesRegularExpression(
            '#adding_joins#i',
            $query,
            "Query SQL did not have parameter check in where clause"
        );

        static::assertMatchesRegularExpression(
            '#(inner )?join [`"]test_related_models[`"] on [`"]test_related_models[`"].[`"]id[`"] '
            . '= [`"]test_simple_models[`"].[`"]test_related_model_id[`"]#i',
            $query,
            "Query SQL does not feature expected join clause"
        );


        // Check if joins are not duplicated.
        $query = (new TestFilter([
            'adding_joins'       => 'okay',
            'no_duplicate_joins' => 'please',
        ]))->apply(TestSimpleModel::query())->toSql();

        static::assertMatchesRegularExpression(
            '#no_duplicate_joins#i',
            $query,
            "Query SQL did not have parameter check in where clause (second param)"
        );

        static::assertMatchesRegularExpression(
            '#(inner )?join [`"]test_related_models[`"] on [`"]test_related_models[`"].[`"]id[`"] '
            . '= [`"]test_simple_models[`"].[`"]test_related_model_id[`"]#i',
            $query,
            "Query SQL does not feature expected join clause (with second param)"
        );

        static::assertEquals(
            1,
            substr_count(strtolower($query), ' join '),
            "Query SQL should have only one join clause"
        );
    }
}
