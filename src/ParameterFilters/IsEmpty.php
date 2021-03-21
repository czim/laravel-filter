<?php

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class IsEmpty implements ParameterFilterInterface
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
     * @param string|null $table
     * @param string|null $column if given, overrules the attribute name
     */
    public function __construct(?string $table = null, ?string $column = null)
    {
        $this->table  = $table;
        $this->column = $column;
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

        return $query->where(
            function ($query) use ($column) {
                return $query->whereNull($column)
                         ->orWhere($column, '');
            }
        );
    }
}
