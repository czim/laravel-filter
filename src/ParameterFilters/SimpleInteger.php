<?php

namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class SimpleInteger implements ParameterFilterInterface
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
     * @var string
     */
    protected $operator;

    /**
     * @param string|null $table
     * @param string|null $column if given, overrules the attribute name
     * @param string      $operator
     */
    public function __construct(?string $table = null, ?string $column = null, string $operator = '=')
    {
        $this->table    = $table;
        $this->column   = $column;
        $this->operator = $operator;
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


        // an array of values: do a whereIn query
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $query->whereIn($column, $value);
            return $query;
        }

        return $query->where($column, $this->operator, $value);
    }
}
