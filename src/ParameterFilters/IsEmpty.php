<?php

declare(strict_types=1);

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @implements ParameterFilterInterface<TModel>
 */
class IsEmpty implements ParameterFilterInterface
{
    /**
     * @param string|null $table
     * @param string|null $column if given, overrules the attribute name
     */
    public function __construct(
        protected readonly ?string $table = null,
        protected readonly ?string $column = null,
    ) {
    }

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
    ): Model|Builder|EloquentBuilder {
        $column = (! empty($this->table) ? $this->table . '.' : null)
            . (! empty($this->column) ? $this->column : $name);

        return $query->where(
            fn (Model|Builder|EloquentBuilder $query) => $query
                ->whereNull($column)
                 ->orWhere($column, '')
        );
    }
}
