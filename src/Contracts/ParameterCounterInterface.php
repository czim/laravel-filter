<?php
namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface ParameterCounterInterface
{
    /**
     * Returns the count for a countable parameter, given the query provided
     *
     * @param string                   $name
     * @param EloquentBuilder          $query
     * @param CountableFilterInterface $filter
     * @return mixed
     */
    public function count($name, $query, CountableFilterInterface $filter);

}
