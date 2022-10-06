<?php

declare(strict_types=1);

namespace Czim\Filter\Test\Helpers;

use Czim\Filter\Filter;
use Czim\Filter\ParameterFilters\SimpleInteger;
use Czim\Filter\ParameterFilters\SimpleString;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use RuntimeException;

class TestFilter extends Filter
{
    /**
     * {@inheritDoc}
     */
    protected string $filterDataClass = TestFilterData::class;

    /**
     * {@inheritDoc}
     */
    protected function strategies(): array
    {
        return [
            'name'     => new SimpleString(),
            'relateds' => new SimpleInteger(null, 'test_related_model_id'),

            'parameter_filter_instance' => new SimpleString('test_simple_models', 'name', true),
            'parameter_filter_string'   => TestParameterFilterByString::class,
            'closure_strategy'          => fn ($name, $value, $query) => $this->closureTestMethod($name, $value, $query),
            'closure_strategy_array'    => [ $this, 'closureTestMethod' ],

            'global_setting'            => Filter::SETTING,

            'invalid_strategy_string'    => 'uninstantiable_string_that_is_not_a_parameter_filter',
            'invalid_strategy_general'   => 13323909823,
            'invalid_strategy_interface' => TranslatableConfig::class, // just any class that is not a ParameterFilterInterface
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

        // Testing joins addition:
        switch ($name) {
            case 'adding_joins':
            case 'no_duplicate_joins':
                $this->addJoin(
                    'UNIQUE_JOIN_KEY',
                    [ 'test_related_models', 'test_related_models.id', '=', 'test_simple_models.test_related_model_id' ]
                );

                $query->where($name, '=', $value);
                return;
        }

        parent::applyParameter($name, $value, $query);
    }

    /**
     * Simple method to test whether closure strategies work.
     *
     * Note that this cannot be a private method, or the [] syntax won't work.
     *
     * @param string                        $name
     * @param mixed                         $value
     * @param Model|Builder|EloquentBuilder $query
     * @return Model|Builder|EloquentBuilder
     */
    protected function closureTestMethod(
        string $name,
        mixed $value,
        Model|Builder|EloquentBuilder $query,
    ): Model|Builder|EloquentBuilder {
        if (! is_array($value) || count($value) !== 2) {
            throw new RuntimeException("Value for '{$name}' not correctly passed through closure!");
        }

        return $query
            ->where('name', '=', $value[0])
            ->where('test_related_model_id', '=', $value[1]);
    }
}
