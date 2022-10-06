<?php

declare(strict_types=1);

namespace Czim\Filter\ParameterCounters;

use Czim\Filter\Contracts\CountableFilterInterface;
use Czim\Filter\Contracts\ParameterCounterInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Counts different distinct values for a single column with configurable aliases.
 */
class SimpleDistinctValue implements ParameterCounterInterface
{
    /**
     * @param string|null $columnName   the column name to count, always used unless null
     * @param bool        $includeEmpty whether to also count for NULL, default is to exclude
     * @param string      $countRaw     the raw SQL count statement ('COUNT(*)')
     * @param string      $columnAlias  an alias for the column ('id')
     * @param string      $countAlias   an alias for the count ('count')
     */
    public function __construct(
        protected readonly ?string $columnName = null,
        protected readonly bool $includeEmpty = false,
        protected readonly string $countRaw = 'count(*)',
        protected readonly string $columnAlias = 'value',
        protected readonly string $countAlias = 'count',
    ) {
    }

    /**
     * Returns the count for a countable parameter, given the query provided.
     *
     * @param string                        $name
     * @param Model|Builder|EloquentBuilder $query
     * @param CountableFilterInterface      $filter
     * @return Collection<string, int>
     */
    public function count(
        string $name,
        Model|Builder|EloquentBuilder $query,
        CountableFilterInterface $filter,
    ): Collection {
        $columnName = $this->determineColumnName($name);

        if (! $this->includeEmpty) {
            $query->whereNotNull($columnName);
        }

        return $query
            ->select([
                "{$columnName} as {$this->columnAlias}",
                DB::raw("{$this->countRaw} as {$this->countAlias}"),
            ])
            ->groupBy($columnName)
            ->pluck($this->countAlias, $this->columnAlias);
    }

    protected function determineColumnName(string $name): string
    {
        // If the columnname is not set, assume an id field based on a table name.
        if (empty($this->columnName)) {
            return $name;
        }

        return $this->columnName;
    }
}
