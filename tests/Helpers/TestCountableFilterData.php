<?php

declare(strict_types=1);

namespace Czim\Filter\Test\Helpers;

use Czim\Filter\FilterData;

class TestCountableFilterData extends FilterData
{
    /**
     * {@inheritDoc}
     */
    protected array $rules = [
        'name'          => 'string',
        'relateds'      => 'array',
        'position'      => 'integer',
        'with_inactive' => 'boolean',
    ];

    /**
     * {@inheritDoc}
     */
    protected array $defaults = [
        'name'          => null,
        'relateds'      => [],
        'position'      => null,
        'with_inactive' => false,
    ];
}
