<?php
namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

interface FilterInterface
{

    /**
     * @param FilterDataInterface $data
     */
    public function setFilterData(FilterDataInterface $data);

    /**
     * @return FilterDataInterface
     */
    public function getFilterData();

    /**
     * Applies the loaded FilterData to a query (builder)
     *
     * @param Model|EloquentBuilder $query
     * @return EloquentBuilder
     */
    public function apply($query);

    /**
     * Adds a query join to be added after all parameters are applied
     *
     * @param string $key           identifying key, used to prevent duplicates
     * @param array  $parameters
     * @param string $joinType      'inner', 'right', defaults to left join
     * @return $this
     */
    public function addJoin($key, array $parameters, $joinType = null);

    /**
     * Getter for settings
     *
     * @param string $name
     * @return mixed
     */
    public function setting($name);

    /**
     * Returns parameter value set in filter data
     *
     * @param string $name
     * @return mixed
     */
    public function parameterValue($name);

}
