<?php

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Simple string comparison on a single column.
 * LIKE/loosy by default, but can be forced to be an exact match.
 */
class SimpleString implements ParameterFilterInterface
{
    /**
     * @var string|null
     */
    protected $table;

    /**
     * @var string|null
     */
    protected $column;

    /**
     * @var bool
     */
    protected $exact;

    /**
     * @param string|null $table
     * @param string|null $column if given, overrules the attribute name
     * @param bool        $exact  whether this should not be a loosy comparison
     */
    public function __construct(?string $table = null, ?string $column = null, bool $exact = false)
    {
        $this->table  = $table;
        $this->column = $column;
        $this->exact  = $exact;
    }

    /**
     * @param string          $name
     * @param mixed           $value
     * @param EloquentBuilder $query
     * @param FilterInterface $filter
     * @return EloquentBuilder
     */
    public function apply(string $name, $value, $query, FilterInterface $filter)
    {
        $column = (! empty($this->table) ? $this->table . '.' : null)
            . (! empty($this->column) ? $this->column : $name);

        $operator = '=';

        if (! $this->exact) {
            $operator = 'LIKE';
            $value    = '%' . $value . '%';
        }

        return $query->where($column, $operator, $value);
    }
}

