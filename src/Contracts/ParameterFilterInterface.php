<?php
namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface ParameterFilterInterface
{
    /**
     * Applies parameter filtering for a given query
     *
     * @param string          $name
     * @param mixed           $value
     * @param EloquentBuilder $query
     * @param FilterInterface $filter
     * @return EloquentBuilder
     */
    public function apply($name, $value, $query, FilterInterface $filter);

}
