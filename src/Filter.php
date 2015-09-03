<?php
namespace Czim\Filter;

use Czim\Filter\Contracts\FilterDataInterface;
use Czim\Filter\Contracts\ParameterFilterInterface;
use Czim\Filter\Exceptions\FilterParameterUnhandledException;
use Czim\Filter\Exceptions\ParameterStrategyInvalidException;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use ReflectionClass;

class Filter implements Contracts\FilterInterface
{
    const SETTING = '_setting_';

    /**
     * The classname for the FilterData that should be constructed
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
     * @var array   associative
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
     * @var array   associative
     */
    protected $settings = [];

    /**
     * Join memory: set join parameters for query->join() calls
     * here, so they may be applied once and without unnecessary or
     * problematic duplication
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Parameter names to be ignored while applying the filter
     * Used by CountableFilter to look up every parameter but
     * the active one. If you use this for other things, be
     * careful.
     *
     * @var array
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
        if ( ! is_a($data, FilterDataInterface::class)) {

            $data = new $this->filterDataClass($data);
        }

        $this->setFilterData($data);

        $this->strategies = $this->strategies();
    }

    /**
     * Sets initial strategies for filtering
     * Override this to set the strategies for your filter.
     *
     * @return array
     */
    protected function strategies()
    {
        return [];
    }

    /**
     * @param FilterDataInterface $data
     */
    public function setFilterData(FilterDataInterface $data)
    {
        $this->data = $data;
    }

    /**
     * @return FilterDataInterface
     */
    public function getFilterData()
    {
        return $this->data;
    }

    /**
     * Setter for global settings
     *
     * @param string $key
     * @param null   $value
     */
    public function setSetting($key, $value = null)
    {
        $this->settings[$key] = $value;
    }

    /**
     * Getter for global settings
     *
     * @param $key
     * @return mixed|null
     */
    public function setting($key)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }

    /**
     * Applies the loaded FilterData to a query (builder)
     *
     * @param Model|EloquentBuilder $query
     * @return EloquentBuilder
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
     * @param $query
     * @throws ParameterStrategyInvalidException
     */
    protected function applyParameters($query)
    {
        $this->storeGlobalSettings();

        $strategies = $this->buildStrategies();

        foreach ($this->data->getApplicableAttributes() as $parameterName) {

            // should we skip it no matter what?
            if ($this->isParameterIgnored($parameterName)) continue;

            // get the value for the filter parameter
            // and if it is empty, we're not filtering by it and should skip it
            $parameterValue = $this->data->getParameterValue($parameterName);

            if ($parameterValue !== false && empty($parameterValue)) continue;


            // find the strategy to be used for applying the filter for this parameter
            // then normalize the strategy so that we can call_user_func on it

            $strategy = isset($strategies[$parameterName]) ? $strategies[$parameterName] : null;

            // is it a global setting, not a normal parameter? skip it
            if ($strategy === static::SETTING) continue;


            if (is_a($strategy, ParameterFilterInterface::class)) {

                $strategy = [ $strategy, 'apply' ];

            } elseif (is_null($strategy)) {
                // default, let it be handled by applyParameter

                $strategy = [ $this, 'applyParameter' ];

            } elseif ( ! is_callable($strategy) && ! is_array($strategy)) {

                throw new ParameterStrategyInvalidException(
                    "Invalid strategy defined for parameter '{$parameterName}',"
                    . " must be ParameterFilterInterface, classname, callable or null"
                );
            }

            // apply the strategy
            call_user_func_array($strategy, [ $parameterName, $parameterValue, $query, $this ]);
        }
    }

    /**
     * Builds up the strategies so that all instantiatable strategies are instantiated
     *
     * @return array
     * @throws ParameterStrategyInvalidException
     */
    protected function buildStrategies()
    {
        foreach ($this->strategies as $parameterName => &$strategy) {

            // only build strategies we will actually use

            if ($this->isParameterIgnored($parameterName)) continue;

            // get the value for the filter parameter
            // and if it is empty, we're not filtering by it and should skip it
            $parameterValue = $this->data->getParameterValue($parameterName);

            if ($parameterValue !== false && empty($parameterValue)) continue;

            // check if the strategy is a string that should be instantiated as a class
            if (is_string($strategy) && $strategy !== static::SETTING) {

                try {

                    $reflection = new ReflectionClass($strategy);

                    if ( ! $reflection->IsInstantiable()) {
                        throw new ParameterStrategyInvalidException("Uninstantiable string provided as strategy for '{$strategy}'");
                    }

                    $strategy = new $strategy();

                } catch (\Exception $e) {

                    throw new ParameterStrategyInvalidException(
                        "Exception thrown while trying to reflect or instantiate string provided as strategy for '{$strategy}'",
                        0, $e
                    );
                }

                // check if it is of the correct type
                if ( ! is_a($strategy, ParameterFilterInterface::class)) {

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
    protected function storeGlobalSettings()
    {
        foreach ($this->strategies as $setting => &$strategy) {

            if ( ! is_string($strategy) || $strategy !== static::SETTING) continue;

            $this->settings[ $setting ] = $this->data->getParameterValue($setting);
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
    protected function applyParameter($parameterName, $parameterValue, $query)
    {
        // default is to always warn that we don't have a strategy
        throw new FilterParameterUnhandledException("No fallback strategy determined for for filter parameter '{$parameterName}'");
    }

    /**
     * Clears the joins memory
     */
    protected function forgetJoins()
    {
        $this->joins = [];
    }

    /**
     * Adds a query join to be added after all parameters are applied
     *
     * @param string $key       identifying key, used to prevent duplicates
     * @param array  $parameters
     */
    public function addJoin($key, array $parameters)
    {
        $this->joins[$key] = $parameters;
    }

    /**
     * Applies joins to the filter-based query
     *
     * @param EloquentBuilder $query
     */
    protected function applyJoins($query)
    {
        foreach ($this->joins as $key => $join) {

            call_user_func_array([ $query, 'join' ], $join);
        }
    }

    /**
     * Ignores a previously ignored parameter for building the filter
     *
     * @param string $parameter
     */
    protected function ignoreParameter($parameter)
    {
        $this->ignoreParameters = array_merge($this->ignoreParameters, [ $parameter ]);
    }

    /**
     * Unignores a previously ignored parameter for building the filter
     *
     * @param string $parameter
     */
    protected function unignoreParameter($parameter)
    {
        $this->ignoreParameters = array_diff($this->ignoreParameters, [ $parameter ]);
    }

    /**
     * Returns whether a parameter name is currently on the ignore list
     *
     * @param $parameterName
     * @return bool
     */
    protected function isParameterIgnored($parameterName)
    {
        if (empty($this->ignoreParameters)) return false;

        return (array_search($parameterName, $this->ignoreParameters) !== false);
    }
}
