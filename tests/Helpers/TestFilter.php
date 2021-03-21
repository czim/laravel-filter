<?php

namespace Czim\Filter\Test\Helpers;

use Czim\Filter\Filter;
use Czim\Filter\ParameterFilters\SimpleInteger;
use Czim\Filter\ParameterFilters\SimpleString;
use RuntimeException;

class TestFilter extends Filter
{
    /**
     * @var string
     */
    protected $filterDataClass = TestFilterData::class;

    /**
     * @return array<string, mixed>
     */
    protected function strategies(): array
    {
        return [
            'name'     => new SimpleString(),
            'relateds' => new SimpleInteger(null, 'test_related_model_id'),

            'parameter_filter_instance' => new SimpleString('test_simple_models', 'name', true),
            'parameter_filter_string'   => TestParameterFilterByString::class,
            'closure_strategy'          => function($name, $value, $query) { $this->closureTestMethod($name, $value, $query); },
            'closure_strategy_array'    => [ $this, 'closureTestMethod' ],

            'global_setting'            => Filter::SETTING,

            'invalid_strategy_string'    => 'uninstantiable_string_that_is_not_a_parameter_filter',
            'invalid_strategy_general'   => 13323909823,
            'invalid_strategy_interface' => TranslatableConfig::class, // just any class that is not a ParameterFilterInterface
        ];
    }

    /**
     * @param string                                $name
     * @param mixed                                 $value
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    protected function applyParameter(string $name, $value, $query)
    {
        // typical with inactive lookup
        // make sure we don't get the the 'no fallback strategy' exception
        if ($name === 'with_inactive') {
            if (! $value) {
                $query->where('active', true);
            }
            return;
        }

        // testing joins addition
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
     * Simple method to test whether closure stratgies work.
     * Note that this cannot be a private method, or the [] syntax won't work.
     *
     * @param string $name
     * @param        $value
     * @param        $query
     * @return mixed
     */
    protected function closureTestMethod(string $name, $value, $query)
    {
        if (! is_array($value) || count($value) !== 2) {
            throw new RuntimeException("Value for '{$name}' not correctly passed through closure!");
        }

        return $query->where('name', '=', $value[0])
            ->where('test_related_model_id', '=', $value[1]);
    }
}
