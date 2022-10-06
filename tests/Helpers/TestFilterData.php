<?php

namespace Czim\Filter\Test\Helpers;

use Czim\Filter\FilterData;

class TestFilterData extends FilterData
{
    /**
     * {@inheritDoc}
     */
    protected $rules = [
        'name'                   => 'string',
        'relateds'               => 'array',
        'position'               => 'integer',
        'with_inactive'          => 'boolean',

        'closure_strategy'       => 'array|size:2',
        'closure_strategy_array' => 'array|size:2',

        'global_setting'         => 'string',
    ];

    /**
     * {@inheritDoc}
     */
    protected $defaults = [
        'name'          => null,
        'relateds'      => [],
        'position'      => null,
        'with_inactive' => false,

        // For tests of the strategy interpretation.
        'no_strategy_set'             => null,
        'no_strategy_set_no_fallback' => null,
        'parameter_filter_instance'   => null,
        'parameter_filter_string'     => null,
        'closure_strategy'            => null,
        'closure_strategy_array'      => null,

        'global_setting'              => null,

        // Testing exceptions for invalid strategies.
        'invalid_strategy_string'     => null,
        'invalid_strategy_general'    => null,
        'invalid_strategy_interface'  => null,

        'adding_joins'       => null,
        'no_duplicate_joins' => null,
    ];
}
