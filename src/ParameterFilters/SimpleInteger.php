<?php

declare(strict_types=1);

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class SimpleInteger implements ParameterFilterInterface
{
    /**
     * @param string|null $table
     * @param string|null $column   if given, overrules the attribute name
     * @param string      $operator =, !=, <, >; only used when the value is not a list
     */
    public function __construct(
        protected readonly ?string $table = null,
        protected readonly ?string $column = null,
        protected readonly string $operator = '=',
    ) {
    }

    public function apply(
        string $name,
        mixed $value,
        Model|Builder|EloquentBuilder $query,
        FilterInterface $filter,
    ): Model|Builder|EloquentBuilder {
        $column = (! empty($this->table) ? $this->table . '.' : null)
            . (! empty($this->column) ? $this->column : $name);


        // If the value is a list, do a whereIn query:
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $query->whereIn($column, $value);
            return $query;
        }

        // Otherwise, do a normal comparison.
        return $query->where($column, $this->operator, $value);
    }
}
