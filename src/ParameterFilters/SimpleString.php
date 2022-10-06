<?php

declare(strict_types=1);

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Simple string comparison on a single column.
 * LIKE/loosy by default, but can be forced to be an exact match.
 */
class SimpleString implements ParameterFilterInterface
{
    /**
     * @param string|null $table
     * @param string|null $column if given, overrules the attribute name
     * @param bool        $exact  whether this should not be a loosy comparison
     */
    public function __construct(
        protected readonly ?string $table = null,
        protected readonly ?string $column = null,
        protected readonly bool $exact = false,
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

        $operator = '=';

        if (! $this->exact) {
            $operator = 'like';
            $value    = '%' . $value . '%';
        }

        return $query->where($column, $operator, $value);
    }
}
