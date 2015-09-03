<?php
namespace Czim\Filter\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface FilterDataInterface
{

    /**
     * Constructor: validate filter data
     *
     * @param array|Arrayable $attributes
     * @param array|Arrayable $defaults     if provided, overrides internal defaults
     */
    public function __construct($attributes, $defaults = null);

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray();

    /**
     * Returns the default values for each applicable attribute
     *
     * @return array
     */
    public function getDefaults();

    /**
     * Gets the attribute names which may be applied
     *
     * @return array
     */
    public function getApplicableAttributes();

    /**
     * Gets the value for a parameter
     *
     * @param $name
     * @return mixed
     */
    public function getParameterValue($name);
}
