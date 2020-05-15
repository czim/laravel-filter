<?php
namespace Czim\Filter\Test\Helpers;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;

class TestParameterFilterByString implements ParameterFilterInterface
{
    public function apply($name, $value, $query, FilterInterface $filter)
    {
        return $query->where('second_field', 'some more');
    }
}
