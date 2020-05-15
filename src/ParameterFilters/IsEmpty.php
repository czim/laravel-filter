<?php
namespace Czim\Filter\ParameterFilters;

use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class IsEmpty implements ParameterFilterInterface
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
     * @param string $table
     * @param string $column   if given, overrules the attribute name
     */
    public function __construct($table = null, $column = null)
    {
        $this->table  = $table;
        $this->column = $column;
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

        return $query->where(function ($query) use ($column) {
            
            return $query->whereNull($column)
                         ->orWhere($column, '');
        });
    }
}
