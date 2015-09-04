<?php
namespace Czim\Filter\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

interface FilterInterface
{

    /**
     * Constructs the relevant FilterData if one is not injected
     *
     * @param array|Arrayable|FilterDataInterface $data
     */
    public function __construct($data);

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
     * @param string $key       identifying key, used to prevent duplicates
     * @param array  $parameters
     */
    public function addJoin($key, array $parameters);

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
