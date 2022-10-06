<?php

namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

interface ParameterCounterInterface
{
    public function count(string $name, Model|Builder|EloquentBuilder $query, CountableFilterInterface $filter): mixed;
}
