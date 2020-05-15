<?php
namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class SimpleInteger implements ParameterFilterInterface
{

    /**
     * @var null
     */
    protected $table;

    /**
     * @var null
     */
    protected $column;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @param string $table
     * @param string $column   if given, overrules the attribute name
     * @param string $operator
     */
    public function __construct($table = null, $column = null, $operator = '=')
    {
        $this->table    = $table;
        $this->column   = $column;
        $this->operator = $operator;
    }

    /**
     * Applies parameter filtering for a given query
     *
     * @param string          $name
     * @param mixed           $value
     * @param EloquentBuilder $query
     * @param FilterInterface $filter
     * @return EloquentBuilder
     */
    public function apply($name, $value, $query, FilterInterface $filter)
    {
        $column = ( ! empty($this->table) ? $this->table . '.' : null)
                . ( ! empty($this->column) ? $this->column : $name);


        // an array of values: do a whereIn query
        if (is_a($value, Arrayable::class)) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $query->whereIn($column, $value);

            return $query;
        }

        // otherwise, do a normal where
        return $query->where($column, $this->operator, $value);
    }
}
