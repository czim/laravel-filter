<?php

namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface ParameterFilterInterface
{
    /**
     * @param string                                 $name
     * @param mixed                                  $value
     * @param TModel|Builder|EloquentBuilder<TModel> $query
     * @param FilterInterface<TModel>                $filter
     * @return TModel|Builder|EloquentBuilder<TModel>
     */
    public function apply(
        string $name,
        mixed $value,
        Model|Builder|EloquentBuilder $query,
        FilterInterface $filter,
    ): Model|Builder|EloquentBuilder;
}
