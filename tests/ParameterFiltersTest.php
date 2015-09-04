<?php
namespace Czim\Filter\Test;

use Czim\Filter\ParameterFilters\SimpleInteger;
use Czim\Filter\ParameterFilters\SimpleString;
use Czim\Filter\Test\Helpers\TestFilter;
use Czim\Filter\Test\Helpers\TestSimpleModel;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ParameterFiltersTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    protected function seedDatabase()
    {
        // don't need this
    }


    // --------------------------------------------
    //      Simple
    // --------------------------------------------

    /**
     * @test
     */
    function simple_string_parameter_filter()
    {
        $filter = new TestFilter([]);

        // loosy by default
        $pfilter = new SimpleString();
        $query = $pfilter->apply('testcol', 'value', TestSimpleModel::query(), $filter);

        $this->assertRegExp(
            '#where ["`]testcol["`] like#i',
            $query->toSql(),
            "Query SQL wrong for loosy default match"
        );
        $this->assertEquals('%value%', $query->getBindings()[0], "Binding not correct for loosy default match");

        // exact match
        $pfilter = new SimpleString(null, null, true);
        $query = $pfilter->apply('testcol', 'value', TestSimpleModel::query(), $filter);

        $this->assertRegExp(
            '#where ["`]testcol["`] =#i',
            $query->toSql(),
            "Query SQL wrong for exact match"
        );
        $this->assertEquals('value', $query->getBindings()[0], "Binding not correct for exact match");

        // custom table and column name
        $pfilter = new SimpleString('custom_table', 'custom_column');
        $query = $pfilter->apply('testcol', 'value', TestSimpleModel::query(), $filter);

        $this->assertRegExp(
            '#where ["`]custom_table["`]\.["`]custom_column["`] like#i',
            $query->toSql(),
            "Query SQL wrong for custom names match"
        );
        $this->assertEquals('%value%', $query->getBindings()[0], "Binding not correct for custom names match");
    }


    /**
     * @test
     */
    function simple_integer_parameter_filter()
    {
        $filter = new TestFilter([]);

        // simple single integer
        $pfilter = new SimpleInteger();
        $query = $pfilter->apply('testcol', 13, TestSimpleModel::query(), $filter);

        $this->assertRegExp(
            '#where ["`]testcol["`] =#i',
            $query->toSql(),
            "Query SQL wrong for default single integer"
        );
        $this->assertEquals(13, $query->getBindings()[0], "Binding not correct for default single integer");

        // custom operator
        $pfilter = new SimpleInteger(null, null, '>');
        $query = $pfilter->apply('testcol', 13, TestSimpleModel::query(), $filter);

        $this->assertRegExp(
            '#where ["`]testcol["`] >#i',
            $query->toSql(),
            "Query SQL wrong for custom operator match"
        );
        $this->assertEquals(13, $query->getBindings()[0], "Binding not correct for custom operator match");

        // custom table and column name
        $pfilter = new SimpleInteger('custom_table', 'custom_column');
        $query = $pfilter->apply('testcol', 13, TestSimpleModel::query(), $filter);

        $this->assertRegExp(
            '#where ["`]custom_table["`]\.["`]custom_column["`] =#i',
            $query->toSql(),
            "Query SQL wrong for integer custom names match"
        );
        $this->assertEquals(13, $query->getBindings()[0], "Binding not correct for integer custom names match");

        // whereIn match (array argument)
        $pfilter = new SimpleInteger();
        $query = $pfilter->apply('testcol', [ 13, 14 ], TestSimpleModel::query(), $filter);

        $this->assertRegExp(
            '#where ["`]testcol["`] in\s*\(\s*\?\s*,\s*\?\s*\)#i',
            $query->toSql(),
            "Query SQL wrong for wherein match"
        );
        $this->assertEquals([13, 14], $query->getBindings(), "Bindings not correct for wherein match");
    }


    // --------------------------------------------
    //      Translations
    // --------------------------------------------

    // todo: add simple test for translated parameterfilter

}
