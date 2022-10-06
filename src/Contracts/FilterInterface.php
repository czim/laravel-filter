<?php

namespace Czim\Filter\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

interface FilterInterface
{
    public function setFilterData(FilterDataInterface $data): void;
    public function getFilterData(): FilterDataInterface;

    public function setSetting(string $key, mixed $value = null): void;
    public function setting(string $key): mixed;

    public function parameterValue(string $name): mixed;

    public function apply(Model|Builder|EloquentBuilder $query): Model|Builder|EloquentBuilder;

    /**
     * Adds a query join to be added after all parameters are applied.
     *
     * @param string               $key      identifying key, used to prevent duplicates
     * @param array<string, mixed> $parameters
     * @param string|null          $joinType 'inner', 'right', defaults to left join
     */
    public function addJoin(string $key, array $parameters, string $joinType = null): void;
}
