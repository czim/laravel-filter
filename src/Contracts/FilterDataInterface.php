<?php
namespace Czim\Filter\Contracts;

interface FilterDataInterface
{

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

    /**
     * Getter for attributes
     *
     * @return array
     */
    public function getAttributes();
}
