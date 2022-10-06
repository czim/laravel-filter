<?php

declare(strict_types=1);

namespace Czim\Filter\Test\Helpers;

use Czim\Filter\CountableFilter;
use Czim\Filter\ParameterFilters;
use Czim\Filter\ParameterCounters;
use Czim\Filter\Test\Helpers\Models\TestSimpleModel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class TestCountableFilter extends CountableFilter
{
    /**
     * {@inheritDoc}
     */
    protected string $filterDataClass = TestCountableFilterData::class;

    /**
     * {@inheritDoc}
     */
    protected array $countables = [
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
     * {@inheritDoc}
     */
    protected function applyParameter(string $name, mixed $value, Model|Builder|EloquentBuilder $query): void
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

    protected function getCountableBaseQuery(?string $parameter = null): Model|Builder|EloquentBuilder
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

    /**
     * {@inheritDoc}
     */
    protected function countParameter(string $parameter, Model|Builder|EloquentBuilder $query): mixed
    {
        return parent::countParameter($parameter, $query);
    }
}
