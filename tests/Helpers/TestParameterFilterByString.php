<?php

namespace Czim\Filter\Test\Helpers;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;

class TestParameterFilterByString implements ParameterFilterInterface
{
    /**
     * @param string                                $name
     * @param mixed                                 $value
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param FilterInterface                       $filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(string $name, $value, $query, FilterInterface $filter)
    {
        return $query->where('second_field', 'some more');
    }
}
