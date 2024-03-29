<?php

declare(strict_types=1);

namespace Czim\Filter\Test\Src;

use Czim\Filter\ParameterFilters\SimpleInteger;
use Czim\Filter\ParameterFilters\SimpleString;
use Czim\Filter\Test\Helpers\Models\TestSimpleModel;
use Czim\Filter\Test\Helpers\TestFilter;
use Czim\Filter\Test\TestCase;

class ParameterFiltersTest extends TestCase
{
    // --------------------------------------------
    //      Simple
    // --------------------------------------------

    /**
     * @test
     */
    public function simple_string_parameter_filter(): void
    {
        $filter = new TestFilter([]);

        // Loosy/like by default.
        $pfilter = new SimpleString();
        $query = $pfilter->apply('testcol', 'value', TestSimpleModel::query(), $filter);

        static::assertMatchesRegularExpression(
            '#where ["`]testcol["`] like#i',
            $query->toSql(),
            'Query SQL wrong for loosy default match'
        );
        static::assertEquals('%value%', $query->getBindings()[0], 'Binding not correct for loosy default match');

        // Exact match.
        $pfilter = new SimpleString(null, null, true);
        $query = $pfilter->apply('testcol', 'value', TestSimpleModel::query(), $filter);

        static::assertMatchesRegularExpression(
            '#where ["`]testcol["`] =#i',
            $query->toSql(),
            'Query SQL wrong for exact match'
        );
        static::assertEquals('value', $query->getBindings()[0], 'Binding not correct for exact match');

        // Custom table and column name.
        $pfilter = new SimpleString('custom_table', 'custom_column');
        $query = $pfilter->apply('testcol', 'value', TestSimpleModel::query(), $filter);

        static::assertMatchesRegularExpression(
            '#where ["`]custom_table["`]\.["`]custom_column["`] like#i',
            $query->toSql(),
            'Query SQL wrong for custom names match'
        );
        static::assertEquals('%value%', $query->getBindings()[0], 'Binding not correct for custom names match');
    }


    /**
     * @test
     */
    public function simple_integer_parameter_filter(): void
    {
        $filter = new TestFilter([]);

        // Simple single integer.
        $pfilter = new SimpleInteger();
        $query = $pfilter->apply('testcol', 13, TestSimpleModel::query(), $filter);

        static::assertMatchesRegularExpression(
            '#where ["`]testcol["`] =#i',
            $query->toSql(),
            'Query SQL wrong for default single integer'
        );
        static::assertEquals(13, $query->getBindings()[0], 'Binding not correct for default single integer');

        // Custom operator.
        $pfilter = new SimpleInteger(null, null, '>');
        $query = $pfilter->apply('testcol', 13, TestSimpleModel::query(), $filter);

        static::assertMatchesRegularExpression(
            '#where ["`]testcol["`] >#i',
            $query->toSql(),
            'Query SQL wrong for custom operator match'
        );
        static::assertEquals(13, $query->getBindings()[0], 'Binding not correct for custom operator match');

        // Custom table and column name.
        $pfilter = new SimpleInteger('custom_table', 'custom_column');
        $query = $pfilter->apply('testcol', 13, TestSimpleModel::query(), $filter);

        static::assertMatchesRegularExpression(
            '#where ["`]custom_table["`]\.["`]custom_column["`] =#i',
            $query->toSql(),
            'Query SQL wrong for integer custom names match'
        );
        static::assertEquals(13, $query->getBindings()[0], 'Binding not correct for integer custom names match');

        // WhereIn match (array argument).
        $pfilter = new SimpleInteger();
        $query = $pfilter->apply('testcol', [ 13, 14 ], TestSimpleModel::query(), $filter);

        static::assertMatchesRegularExpression(
            '#where ["`]testcol["`] in\s*\(\s*\?\s*,\s*\?\s*\)#i',
            $query->toSql(),
            'Query SQL wrong for wherein match'
        );
        static::assertEquals([13, 14], $query->getBindings(), 'Bindings not correct for wherein match');
    }
}
