<?php

declare(strict_types=1);

namespace Czim\Filter;

use Czim\Filter\Contracts\CountableFilterInterface;
use Czim\Filter\Contracts\FilterDataInterface;
use Czim\Filter\Contracts\ParameterCounterInterface;
use Czim\Filter\Exceptions\FilterParameterUnhandledException;
use Czim\Filter\Exceptions\ParameterStrategyInvalidException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use ReflectionClass;
use Throwable;

/**
 * The point is to get an overview of things that may be alternatively filtered
 * for if *only* that particular attribute is altered. If you're filtering by,
 * say, product line and brand, then this should look up:
 *      the number of matches for all brands that also match the product line filter,
 *      and the number of matches for all product lines that also match the brand filter
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Filter<TModel>
 * @implements CountableFilterInterface<TModel>
 */
abstract class CountableFilter extends Filter implements CountableFilterInterface
{
    /**
     * Which filter parameters are 'countable' -- (should) have implementations
     * for the getCounts() method. This is what's used to determine which other
     * filter options (f.i. brands, product lines) to show for the current selection
     *
     * @var string[]
     */
    protected array $countables = [];

    /**
     * Application strategies for all countables to get counts for.
     *
     * Just like the strategies property, but now for getCount()
     *
     * These can be either:
     *      an instance of ParameterCounterInterface,
     *      a string classname of an instantiatable ParameterCounterFilter,
     *      a callback that follows the same logic as ParameterCounterFilter->count()
     *      null, which means that getCountForParameter() will be called on the Filter
     *          itself, which MUST then be able to handle it!
     *
     * @var array<string, ParameterCounterInterface<TModel>|class-string<ParameterCounterInterface<TModel>>|callable|null> by name
     */
    protected array $countStrategies = [];

    /**
     * List of countables that should not be included in getCount() results.
     *
     * @var string[]
     */
    protected array $ignoreCountables = [];

    /**
     * List of countables that should be applied even when performing a count for that same countable.
     *
     * Set this, for instance, for plural AND-applied checkbox filters where every check should further
     * restrict the available options.
     *
     * @var string[]
     */
    protected array $includeSelfInCount = [];


    /**
     * Returns new base query object to build countable query on.
     *
     * This will be called for each countable parameter, and could be
     * something like: EloquentModelName::query();
     *
     * @param string|null $parameter name of the countable parameter
     * @return TModel|Builder|EloquentBuilder<TModel>
     */
    abstract protected function getCountableBaseQuery(?string $parameter = null): Model|Builder|EloquentBuilder;


    /**
     * {@inheritDoc}
     */
    public function __construct(array|FilterDataInterface $data)
    {
        parent::__construct($data);

        $this->countStrategies = $this->countStrategies();
    }

    /**
     * Initializes strategies for counting countables.
     *
     * Override this to set the countable strategies for your filter.
     *
     * @return array<string, ParameterCounterInterface<TModel>|class-string<ParameterCounterInterface<TModel>>|callable|null> by name
     */
    protected function countStrategies(): array
    {
        return [];
    }

    /**
     * Returns a list of the countable parameters to get counts for.
     *
     * @return string[]
     */
    public function getCountables(): array
    {
        return $this->countables;
    }

    /**
     * Returns a list of the countable parameters that are not ignored
     *
     * @return string[]
     */
    protected function getActiveCountables(): array
    {
        return array_diff($this->getCountables(), $this->ignoreCountables);
    }

    /**
     * Gets alternative counts per (relevant) attribute for the filter data.
     *
     * @param string[] $countables overrides ignoredCountables
     * @return CountableResults<string, mixed>
     * @throws ParameterStrategyInvalidException
     */
    public function getCounts(array $countables = []): CountableResults
    {
        $counts = new CountableResults();

        $strategies = $this->normalizeCountableStrategies();

        // Determine which countables to count for.
        if (! empty($countables)) {
            $countables = array_intersect($this->getCountables(), $countables);
        } else {
            $countables = $this->getActiveCountables();
        }

        foreach ($countables as $parameterName) {
            // Should we skip it no matter what?
            if ($this->isCountableIgnored($parameterName)) {
                continue;
            }

            $strategy = $strategies[ $parameterName ] ?? null;

            // normalize the strategy so that we can call_user_func on it
            if ($strategy instanceof ParameterCounterInterface) {
                $strategy = [ $strategy, 'count' ];
            } elseif ($strategy === null) {
                // default, let it be handled by applyParameter
                $strategy = [ $this, 'countParameter' ];
            } elseif (! is_callable($strategy)) {
                throw new ParameterStrategyInvalidException(
                    "Invalid counting strategy defined for parameter '{$parameterName}',"
                    . ' must be ParameterFilterInterface, classname, callable or null'
                );
            }

            // Start with a fresh query.
            $query = $this->getCountableBaseQuery();

            // Apply the filter while temporarily ignoring the current countable parameter,
            // unless it is forced to be included.
            $includeSelf = in_array($parameterName, $this->includeSelfInCount);

            if (! $includeSelf) {
                $this->ignoreParameter($parameterName);
            }

            $this->apply($query);

            if (! $includeSelf) {
                $this->unignoreParameter($parameterName);
            }

            /** @var callable $strategy */

            // Retrieve the count and put it in the results.
            $counts->put($parameterName, $strategy($parameterName, $query, $this));
        }

        return $counts;
    }

    /**
     * Get count result for a parameter's records, given the filter settings for other parameters.
     * this is the fall-back for when no other strategy is configured in $this->countStrategies.
     *
     * Override this if you need to use it in a specific Filter instance
     *
     * @param string                                 $parameter countable name
     * @param TModel|Builder|EloquentBuilder<TModel> $query
     * @return mixed
     * @throws FilterParameterUnhandledException
     */
    protected function countParameter(string $parameter, Model|Builder|EloquentBuilder $query): mixed
    {
        // Default is to always warn that we don't have a strategy.
        throw new FilterParameterUnhandledException(
            "No fallback strategy determined for for countable parameter '{$parameter}'"
        );
    }

    /**
     * Builds up the strategies so that all instantiatable strategies are instantiated.
     *
     * @return array<string, ParameterCounterInterface<TModel>|callable|null> by name
     * @throws ParameterStrategyInvalidException
     */
    protected function normalizeCountableStrategies(): array
    {
        foreach ($this->countStrategies as &$strategy) {
            // check if the strategy is a string that should be instantiated as a class
            if (! is_string($strategy)) {
                continue;
            }

            /** @var class-string<ParameterCounterInterface<TModel>> $strategy */

            try {
                $reflection = new ReflectionClass($strategy);

                if (! $reflection->isInstantiable()) {
                    throw new ParameterStrategyInvalidException(
                        "Uninstantiable string provided as countStrategy for '{$strategy}'"
                    );
                }

                $strategy = new $strategy();
            } catch (Throwable $e) {
                throw new ParameterStrategyInvalidException(
                    'Exception thrown while trying to reflect or instantiate string '
                    . "provided as countStrategy for '{$strategy}'",
                    0,
                    $e
                );
            }

            // Check if it is of the correct type.
            if (! $strategy instanceof ParameterCounterInterface) {
                throw new ParameterStrategyInvalidException(
                    "Instantiated string provided is not a ParameterFilter: '" . get_class($strategy) . "'"
                );
            }
        }

        unset($strategy);

        return $this->countStrategies;
    }

    /**
     * Disables a countable when getCounts() is invoked.
     *
     * Note that this differs from ignoreParameter in that the count itself is omitted, but it does not
     * affect what parameters get applied to the queries for the other countables!
     *
     * @param string $countable
     */
    public function ignoreCountable(string $countable): void
    {
        $this->ignoreCountables = array_merge($this->ignoreCountables, [$countable]);
    }

    /**
     * Disables a number of countables when getCounts() is invoked.
     *
     * @param string[] $countables
     */
    public function ignoreCountables(array $countables): void
    {
        array_map([$this, 'ignoreCountable'], $countables);
    }

    /**
     * Re-enables a countable when getCounts() is invoked.
     *
     * @param string $countable
     */
    public function unignoreCountable(string $countable): void
    {
        $this->ignoreCountables = array_diff($this->ignoreCountables, [$countable]);
    }

    /**
     * Re-enables a number of countables when getCounts() is invoked.
     *
     * @param string[] $countables
     */
    public function unignoreCountables(array $countables): void
    {
        array_map([$this, 'unignoreCountable'], $countables);
    }

    public function isCountableIgnored(string $countableName): bool
    {
        if (empty($this->ignoreCountables)) {
            return false;
        }

        return in_array($countableName, $this->ignoreCountables, true);
    }
}
