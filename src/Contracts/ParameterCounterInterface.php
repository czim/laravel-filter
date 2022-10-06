<?php

namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface ParameterCounterInterface
{
    /**
     * @param string                                 $name
     * @param TModel|Builder|EloquentBuilder<TModel> $query
     * @param CountableFilterInterface<TModel>       $filter
     * @return mixed
     */
    public function count(string $name, Model|Builder|EloquentBuilder $query, CountableFilterInterface $filter): mixed;
}
