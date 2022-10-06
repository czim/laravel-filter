<?php

namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

interface FilterInterface
{
    public function setFilterData(FilterDataInterface $data): void;
    public function getFilterData(): FilterDataInterface;

    /**
     * Setter for global settings.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setSetting(string $key, $value = null): void;

    /**
     * Getter for global settings.
     *
     * @param string $key
     * @return mixed
     */
    public function setting(string $key);

    /**
     * Returns parameter value set in filter data.
     *
     * @param string $name
     * @return mixed
     */
    public function parameterValue(string $name);

    /**
     * Applies the loaded FilterData to a query builder.
     *
     * @param Model|EloquentBuilder $query
     * @return EloquentBuilder
     */
    public function apply($query);

    /**
     * Adds a query join to be added after all parameters are applied.
     *
     * @param string               $key      identifying key, used to prevent duplicates
     * @param array<string, mixed> $parameters
     * @param string|null          $joinType 'inner', 'right', defaults to left join
     */
    public function addJoin(string $key, array $parameters, string $joinType = null): void;
}
