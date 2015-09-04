<?php
namespace Czim\Filter\Test\Helpers;

use Czim\Filter\CountableFilter;
use Czim\Filter\ParameterFilters;
use Czim\Filter\ParameterCounters;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class TestCountableFilter extends CountableFilter
{

    // Filter methods / configuration

    protected $filterDataClass = TestCountableFilterData::class;

    protected function strategies()
    {
        return [
            'name'     => new ParameterFilters\SimpleString(),
            'position' => new ParameterFilters\SimpleInteger(),
            'relateds' => new ParameterFilters\SimpleInteger(null, 'test_related_model_id'),
        ];
    }

    protected function applyParameter($name, $value, $query)
    {
        // typical with inactive lookup
        // make sure we don't get the the 'no fallback strategy' exception
        if ($name == 'with_inactive') {

            if ( ! $value) {
                $query->where('active', true);
            }

            return $query;
        }

        parent::applyParameter($name, $value, $query);
    }


    // CountableFilter methods / configuration

    protected $countables = [
        'position',
        'relateds',
    ];

    protected function getCountableBaseQuery($parameter = null)
    {
        return TestSimpleModel::query();
    }

    protected function countStrategies()
    {
        return [
            'position' => new ParameterCounters\SimpleDistinctValue(),
            'relateds' => new ParameterCounters\SimpleBelongsTo('test_related_model_id'),
        ];
    }

    protected function countParameter($parameter, $query)
    {

        parent::countParameter($parameter, $query);
    }

}
