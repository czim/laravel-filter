<?php

declare(strict_types=1);

namespace Czim\Filter;

use Czim\Filter\Contracts\FilterDataInterface;
use Czim\Filter\Contracts\FilterInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Czim\Filter\Enums\JoinType;
use Czim\Filter\Exceptions\FilterParameterUnhandledException;
use Czim\Filter\Exceptions\ParameterStrategyInvalidException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use ReflectionClass;
use Throwable;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @implements FilterInterface<TModel>
 */
class Filter implements FilterInterface
{
    public const SETTING = '_setting_';

    protected const JOIN_METHOD_INNER = 'join';
    protected const JOIN_METHOD_LEFT  = 'leftJoin';
    protected const JOIN_METHOD_RIGHT = 'rightJoin';

    /**
     * The classname for the FilterData that should be constructed when the filter is
     * constructed with plain array data.
     *
     * @var class-string<FilterDataInterface>
     */
    protected string $filterDataClass = FilterData::class;

    /**
     * Application strategies for all parameters/attributes to apply for
     *
     * These can be either:
     *      an instance of ParameterFilter,
     *      a string equal to Filter::SETTING, in which case its value will be
     *          stored as a 'global setting' for the filter instead
     *      a string classname of an instantiatable ParameterFilter,
     *      a callback that follows the same logic as ParameterFilter->apply()
     *      null, which means that applyParameter() will be called on the Filter
     *          itself, which MUST then be able to handle it!
     *
     * @var array<string, ParameterFilterInterface<TModel>|class-string<ParameterFilterInterface<TModel>>|string|callable|null> by name
     */
    protected array $strategies = [];

    protected FilterDataInterface $data;

    /**
     * Settings for the filter, filled automatically for parameters that have the Filter::SETTING strategy flag set.
     *
     * @var array<string, mixed>
     */
    protected array $settings = [];

    /**
     * Join memory: set join parameters for query->join() calls here, so they may be applied once and
     * without unnecessary or problematic duplication.
     *
     * @var array<string, array<int, string>> keyed by identifying string/name
     */
    protected array $joins = [];

    /**
     * Join memory for type of join, defaults to left.
     * Must be keyed by join identifier key.
     *
     * @var array<string, string>
     */
    protected array $joinTypes = [];

    /**
     * Parameter names to be ignored while applying the filter
     * Used by CountableFilter to look up every parameter but the active one.
     * If you use this for other things, be careful.
     *
     * @var string[]
     */
    protected array $ignoreParameters = [];


    /**
     * @param array<string, mixed>|FilterDataInterface $data
     */
    public function __construct(array|FilterDataInterface $data)
    {
        // create FilterData if provided data is not already
        if (! $data instanceof FilterDataInterface) {
            $data = $this->instantiateFilterData($data);
        }

        $this->setFilterData($data);

        $this->strategies = $this->strategies();
    }


    public function setFilterData(FilterDataInterface $data): void
    {
        assert(is_a($data, $this->filterDataClass), 'Filter data must match configured data class');

        $this->data = $data;
    }

    public function getFilterData(): FilterDataInterface
    {
        return $this->data;
    }

    public function setSetting(string $key, mixed $value = null): void
    {
        $this->settings[$key] = $value;
    }

    public function setting(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }

    public function parameterValue(string $name): mixed
    {
        return $this->data->getParameterValue($name);
    }

    /**
     * Applies the loaded FilterData to a query (builder).
     *
     * @param TModel|Builder|EloquentBuilder<TModel> $query
     * @return TModel|Builder|EloquentBuilder<TModel>
     * @throws ParameterStrategyInvalidException
     */
    public function apply(Model|Builder|EloquentBuilder $query): Model|Builder|EloquentBuilder
    {
        $this->forgetJoins();
        $this->applyParameters($query);
        $this->applyJoins($query);

        return $query;
    }

    /**
     * Applies all filter parameters to the query, using the configured strategies.
     *
     * @param TModel|Builder|EloquentBuilder<TModel> $query
     * @throws ParameterStrategyInvalidException
     */
    protected function applyParameters(Model|Builder|EloquentBuilder $query): void
    {
        $this->storeGlobalSettings();

        $strategies = $this->buildStrategies();

        foreach ($this->data->getApplicableAttributes() as $parameterName) {
            if ($this->isParameterIgnored($parameterName)) {
                continue;
            }

            // Get the value for the filter parameter and if it is empty,
            // we're not filtering by it and should skip it.
            $parameterValue = $this->data->getParameterValue($parameterName);

            if ($this->isParameterValueUnset($parameterName, $parameterValue)) {
                continue;
            }


            // Find the strategy to be used for applying the filter for this parameter
            // then normalize the strategy so that we can call_user_func on it.
            $strategy = $strategies[$parameterName] ?? null;

            // Is it a global setting, not a normal parameter? Skip it.
            if ($strategy === static::SETTING) {
                continue;
            }


            if ($strategy instanceof ParameterFilterInterface) {
                $strategy = [ $strategy, 'apply' ];
            } elseif ($strategy === null) {
                // Default, let it be handled by applyParameter
                $strategy = [ $this, 'applyParameter' ];
            } elseif (! is_callable($strategy)) {
                throw new ParameterStrategyInvalidException(
                    "Invalid strategy defined for parameter '{$parameterName}',"
                    . ' must be ParameterFilterInterface, classname, callable or null'
                );
            }

            /** @var callable $strategy */
            $strategy($parameterName, $parameterValue, $query, $this);
        }
    }

    /**
     * Builds up the strategies so that all instantiatable strategies are instantiated.
     *
     * @return array<string, ParameterFilterInterface<TModel>|string|callable|null> by name
     * @throws ParameterStrategyInvalidException
     */
    protected function buildStrategies(): array
    {
        foreach ($this->strategies as $parameterName => &$strategy) {
            if ($this->isParameterIgnored($parameterName)) {
                continue;
            }

            // Get the value for the filter parameter and if it is empty,
            // we're not filtering by it and should skip it.
            $parameterValue = $this->parameterValue($parameterName);

            if ($this->isParameterValueUnset($parameterName, $parameterValue)) {
                continue;
            }

            // Check if the strategy is a string that should be instantiated as a class.
            if (! is_string($strategy) || $strategy === static::SETTING) {
                continue;
            }

            /** @var class-string<ParameterFilterInterface<TModel>> $strategy */

            try {
                $reflection = new ReflectionClass($strategy);

                if (! $reflection->IsInstantiable()) {
                    throw new ParameterStrategyInvalidException(
                        "Uninstantiable string provided as strategy for '{$strategy}'"
                    );
                }

                $strategy = new $strategy();
            } catch (Throwable $exception) {
                throw new ParameterStrategyInvalidException(
                    "Exception thrown while trying to reflect or instantiate strategy string for '{$strategy}'",
                    0,
                    $exception
                );
            }

            // check if it is of the correct type
            if (! $strategy instanceof ParameterFilterInterface) {
                throw new ParameterStrategyInvalidException(
                    "Instantiated string provided is not a ParameterFilter: '" . get_class($strategy) . "'"
                );
            }
        }

        unset($strategy);

        return $this->strategies;
    }

    /**
     * Interprets parameters with the SETTING string and stores their current values in the settings property.
     *
     * This must be done before the parameters are applied, if the settings are to have any effect.
     * Note that you must add your own interpretation & effect for settings in your FilterParameter
     * methods/classes (use the setting() getter).
     */
    protected function storeGlobalSettings(): void
    {
        foreach ($this->strategies as $setting => &$strategy) {
            if ($strategy !== static::SETTING) {
                continue;
            }

            $this->settings[$setting] = $this->parameterValue($setting);
        }
    }

    /**
     * Applies filter to the query for an attribute/parameter with the given parameter value,
     * this is the fall-back for when no other strategy is configured in $this->strategies.
     *
     * Override this if you need to use it in a specific Filter instance.
     *
     * @param string                                 $name
     * @param mixed|null                             $value
     * @param TModel|Builder|EloquentBuilder<TModel> $query
     * @throws FilterParameterUnhandledException
     */
    protected function applyParameter(string $name, mixed $value, Model|Builder|EloquentBuilder $query): void
    {
        // Default is to always warn that we don't have a strategy.
        throw new FilterParameterUnhandledException(
            "No fallback strategy determined for for filter parameter '{$name}'"
        );
    }

    protected function forgetJoins(): void
    {
        $this->joins = [];
    }

    /**
     * Adds a query join to be added after all parameters are applied.
     *
     * @param string             $key         identifying key, used to prevent duplicates
     * @param array<int, string> $parameters
     * @param string|null        $joinType   {@link JoinType} 'join'/'inner', 'right'; defaults to left join
     */
    public function addJoin(string $key, array $parameters, ?string $joinType = null): void
    {
        if ($joinType !== null) {
            if ($joinType === JoinType::INNER || str_contains($joinType, 'inner')) {
                $this->joinTypes[$key] = static::JOIN_METHOD_INNER;
            } elseif ($joinType === JoinType::RIGHT) {
                $this->joinTypes[$key] = static::JOIN_METHOD_RIGHT;
            } else {
                unset($this->joinTypes[$key]); // let it default to left join.
            }
        }

        $this->joins[$key] = $parameters;
    }

    /**
     * @param Model|Builder|EloquentBuilder<TModel> $query
     */
    protected function applyJoins(Model|Builder|EloquentBuilder $query): void
    {
        foreach ($this->joins as $key => $join) {
            $joinMethod = $this->joinTypes[ $key ] ?? static::JOIN_METHOD_LEFT;

            $query->{$joinMethod}(...$join);
        }
    }

    protected function isParameterValueUnset(string $parameter, mixed $value): bool
    {
        return $value !== false && empty($value);
    }

    protected function ignoreParameter(string $parameter): void
    {
        $this->ignoreParameters = array_merge($this->ignoreParameters, [$parameter]);
    }

    protected function unignoreParameter(string $parameter): void
    {
        $this->ignoreParameters = array_diff($this->ignoreParameters, [$parameter]);
    }

    protected function isParameterIgnored(string $parameterName): bool
    {
        if (empty($this->ignoreParameters)) {
            return false;
        }

        return in_array($parameterName, $this->ignoreParameters, true);
    }


    /**
     * @param array<string, mixed> $data
     * @return FilterDataInterface
     */
    protected function instantiateFilterData(array $data): FilterDataInterface
    {
        return new $this->filterDataClass($data);
    }

    /**
     * Initializes strategies for filtering.
     *
     * Override this to set the strategies for your filter.
     *
     * @return array<string, ParameterFilterInterface<TModel>|class-string<ParameterFilterInterface<TModel>>|string|callable|null>
     */
    protected function strategies(): array
    {
        return [];
    }
}
