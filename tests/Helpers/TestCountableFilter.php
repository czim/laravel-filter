<?php

namespace Czim\Filter\Test\Helpers;

use Czim\Filter\CountableFilter;
use Czim\Filter\ParameterFilters;
use Czim\Filter\ParameterCounters;
use Czim\Filter\Test\Helpers\Models\TestSimpleModel;

class TestCountableFilter extends CountableFilter
{
    /**
     * @var string
     */
    protected $filterDataClass = TestCountableFilterData::class;

    /**
     * @var string[]
     */
    protected $countables = [
        'position',
        'relateds',
    ];

    /**
     * {@inheritDoc}
     */
    protected function strategies(): array
    {
        return [
            'name'     => new ParameterFilters\SimpleString(),
            'position' => new ParameterFilters\SimpleInteger(),
            'relateds' => new ParameterFilters\SimpleInteger(null, 'test_related_model_id'),
        ];
    }

    /**
     * @param string                                $name
     * @param mixed                                 $value
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    protected function applyParameter(string $name, $value, $query)
    {
        // Typical with inactive lookup.
        // Make sure we don't get the 'no fallback strategy' exception.
        if ($name === 'with_inactive') {
            if (! $value) {
                $query->where('active', true);
            }

            return;
        }

        parent::applyParameter($name, $value, $query);
    }

    protected function getCountableBaseQuery(?string $parameter = null)
    {
        return TestSimpleModel::query();
    }

    /**
     * {@inheritDoc}
     */
    protected function countStrategies(): array
    {
        return [
            'position' => new ParameterCounters\SimpleDistinctValue(),
            'relateds' => new ParameterCounters\SimpleBelongsTo('test_related_model_id'),
        ];
    }

    protected function countParameter(string $parameter, $query)
    {
        parent::countParameter($parameter, $query);
    }
}
