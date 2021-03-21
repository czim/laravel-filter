<?php

namespace Czim\Filter;

use Czim\Filter\Contracts\FilterDataInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Czim\Filter\Exceptions\FilterParameterUnhandledException;
use Czim\Filter\Exceptions\ParameterStrategyInvalidException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Throwable;

class Filter implements Contracts\FilterInterface
{
    public const SETTING = '_setting_';

    /**
     * The classname for the FilterData that should be constructed.
     *
     * @var string
     */
    protected $filterDataClass = FilterData::class;

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
     * @var array<string, mixed> by parameter name
     */
    protected $strategies = [];

    /**
     * @var FilterDataInterface
     */
    protected $data;

    /**
     * Settings for the filter, filled automatically for
     * parameters that have the Filter::SETTING strategy flag set
     *
     * @var array<string, mixed>
     */
    protected $settings = [];

    /**
     * Join memory: set join parameters for query->join() calls
     * here, so they may be applied once and without unnecessary or
     * problematic duplication
     *
     * @var array<string, mixed>
     */
    protected $joins = [];

    /**
     * Join memory for type of join, defaults to left.
     * Must be keyed by join identifier key
     *
     * @var array<string, string>
     */
    protected $joinTypes = [];

    /**
     * Parameter names to be ignored while applying the filter
     * Used by CountableFilter to look up every parameter but the active one.
     * If you use this for other things, be careful.
     *
     * @var string[]
     */
    protected $ignoreParameters = [];

    /**
     * Constructs the relevant FilterData if one is not injected
     *
     * @param array|Arrayable|FilterDataInterface $data
     */
    public function __construct($data)
    {
        // create FilterData if provided data is not already
        if (! $data instanceof FilterDataInterface) {
            $data = new $this->filterDataClass($data);
        }

        $this->setFilterData($data);

        $this->strategies = $this->strategies();
    }

    /**
     * Initializes strategies for filtering.
     * Override this to set the strategies for your filter.
     *
     * @return array<string, mixed>
     */
    protected function strategies(): array
    {
        return [];
    }

    public function setFilterData(FilterDataInterface $data): void
    {
        $this->data = $data;
    }

    public function getFilterData(): FilterDataInterface
    {
        return $this->data;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setSetting(string $key, $value = null): void
    {
        $this->settings[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function setting(string $key)
    {
        return $this->settings[$key] ?? null;
    }

    /**
     * Returns parameter value set in filter data.
     * Convenience method.
     *
     * @param string $name
     * @return mixed
     */
    public function parameterValue(string $name)
    {
        return $this->data->getParameterValue($name);
    }

    /**
     * Applies the loaded FilterData to a query (builder)
     *
     * @param Model|EloquentBuilder $query
     * @return EloquentBuilder
     * @throws ParameterStrategyInvalidException
     */
    public function apply($query)
    {
        $this->forgetJoins();
        $this->applyParameters($query);
        $this->applyJoins($query);

        return $query;
    }

    /**
     * Applies all filter parameters to the query, using the configured strategies
     *
     * @param Model|EloquentBuilder $query
     * @throws ParameterStrategyInvalidException
     */
    protected function applyParameters($query)
    {
        $this->storeGlobalSettings();

        $strategies = $this->buildStrategies();

        foreach ($this->data->getApplicableAttributes() as $parameterName) {
            // Should we skip it no matter what?
            if ($this->isParameterIgnored($parameterName)) {
                continue;
            }

            // Get the value for the filter parameter
            // and if it is empty, we're not filtering by it and should skip it.
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
            } elseif (! is_callable($strategy) && ! is_array($strategy)) {
                throw new ParameterStrategyInvalidException(
                    "Invalid strategy defined for parameter '{$parameterName}',"
                    . " must be ParameterFilterInterface, classname, callable or null"
                );
            }

            $strategy($parameterName, $parameterValue, $query, $this);
        }
    }

    /**
     * Builds up the strategies so that all instantiatable strategies are instantiated.
     *
     * @return array<string, mixed>
     * @throws ParameterStrategyInvalidException
     */
    protected function buildStrategies(): array
    {
        foreach ($this->strategies as $parameterName => &$strategy) {
            // only build strategies we will actually use
            if ($this->isParameterIgnored($parameterName)) {
                continue;
            }

            // get the value for the filter parameter
            // and if it is empty, we're not filtering by it and should skip it
            $parameterValue = $this->parameterValue($parameterName);

            if ($this->isParameterValueUnset($parameterName, $parameterValue)) {
                continue;
            }

            // check if the strategy is a string that should be instantiated as a class
            if (is_string($strategy) && $strategy !== static::SETTING) {
                try {
                    $reflection = new ReflectionClass($strategy);

                    if (! $reflection->IsInstantiable()) {
                        throw new ParameterStrategyInvalidException(
                            "Uninstantiable string provided as strategy for '{$strategy}'"
                        );
                    }

                    $strategy = new $strategy();
                } catch (Throwable $e) {
                    throw new ParameterStrategyInvalidException(
                        "Exception thrown while trying to reflect or instantiate string provided as strategy for '{$strategy}'",
                        0, $e
                    );
                }

                // check if it is of the correct type
                if (! $strategy instanceof ParameterFilterInterface) {
                    throw new ParameterStrategyInvalidException(
                        "Instantiated string provided is not a ParameterFilter: '" . get_class($strategy) . "'"
                    );
                }
            }
        }

        unset($strategy);

        return $this->strategies;
    }

    /**
     * Interprets parameters with the SETTING string and stores their
     * current values in the settings property. This must be done before
     * the parameters are applied, if the settings are to have any effect
     *
     * Note that you must add your own interpretation & effect for settings
     * in your FilterParameter methods/classes (use the setting() getter)
     */
    protected function storeGlobalSettings(): void
    {
        foreach ($this->strategies as $setting => &$strategy) {
            if (! is_string($strategy) || $strategy !== static::SETTING) {
                continue;
            }

            $this->settings[$setting] = $this->parameterValue($setting);
        }
    }

    /**
     * Applies filter to the query for an attribute/parameter with the given parameter value,
     * this is the fall-back for when no other strategy is configured in $this->strategies.
     *
     * Override this if you need to use it in a specific Filter instance
     *
     * @param string          $parameterName
     * @param mixed|null      $parameterValue
     * @param EloquentBuilder $query
     * @throws FilterParameterUnhandledException
     */
    protected function applyParameter(string $parameterName, $parameterValue, $query)
    {
        // Default is to always warn that we don't have a strategy.
        throw new FilterParameterUnhandledException(
            "No fallback strategy determined for for filter parameter '{$parameterName}'"
        );
    }

    /**
     * Clears the joins memory.
     */
    protected function forgetJoins(): void
    {
        $this->joins = [];
    }

    /**
     * Adds a query join to be added after all parameters are applied
     *
     * @param string      $key        identifying key, used to prevent duplicates
     * @param mixed[]     $parameters
     * @param string|null $joinType   'inner', 'right', defaults to left join
     */
    public function addJoin(string $key, array $parameters, ?string $joinType = null): void
    {
        if ($joinType !== null) {
            if ($joinType === 'join' || stripos($joinType, 'inner') !== false) {
                $this->joinTypes[$key] = 'join';
            } elseif (stripos($joinType, 'right') !== false) {
                $this->joinTypes[$key] = 'rightJoin';
            } else {
                unset($this->joinTypes[$key]);
            }
        }

        $this->joins[$key] = $parameters;
    }

    /**
     * Applies joins to the filter-based query.
     *
     * @param EloquentBuilder $query
     */
    protected function applyJoins($query): void
    {
        foreach ($this->joins as $key => $join) {
            $joinMethod = $this->joinTypes[ $key ] ?? 'leftJoin';

            call_user_func_array([ $query, $joinMethod ], $join);
        }
    }

    /**
     * Returns whether a given parameter's value should be treated as empty or unset.
     *
     * @param string $parameter
     * @param mixed  $value
     * @return bool
     */
    protected function isParameterValueUnset(string $parameter, $value): bool
    {
        return $value !== false && empty($value);
    }

    /**
     * Ignores a previously ignored parameter for building the filter.
     *
     * @param string $parameter
     */
    protected function ignoreParameter(string $parameter): void
    {
        $this->ignoreParameters = array_merge($this->ignoreParameters, [$parameter]);
    }

    /**
     * Unignores a previously ignored parameter for building the filter.
     *
     * @param string $parameter
     */
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
}
