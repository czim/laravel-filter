<?php
namespace Czim\Filter\Test\Helpers;

use Czim\Filter\FilterData;

class TestFilterData extends FilterData
{
    protected $rules = [
        'name'          => 'string',
        'relateds'      => 'array',
        'position'      => 'integer',
        'with_inactive' => 'boolean',

        // for tests of the strategy interpretation
        'no_strategy_set'             => 'string',
        'no_strategy_set_no_fallback' => 'string',
        'parameter_filter_instance'   => 'string',
        'parameter_filter_string'     => 'string',
        'closure_strategy'            => 'array|size:2',
        'closure_strategy_array'      => 'array|size:2',

        'global_setting'              => 'string',

        // testing exceptions for invalid strategies
        'invalid_strategy_string'     => 'string',
        'invalid_strategy_general'    => 'string',
        'invalid_strategy_interface'  => 'string',
    ];

    protected $defaults = [
        'name'          => null,
        'relateds'      => [],
        'position'      => null,
        'with_inactive' => false,

        'no_strategy_set'             => null,
        'no_strategy_set_no_fallback' => null,
        'parameter_filter_instance'   => null,
        'parameter_filter_string'     => null,
        'closure_strategy'            => null,
        'closure_strategy_array'      => null,

        'global_setting'              => null,

        'invalid_strategy_string'     => null,
        'invalid_strategy_general'    => null,
        'invalid_strategy_interface'  => null,
    ];
}
