<?php
namespace Czim\Filter;

use Czim\Filter\Exceptions\FilterDataValidationFailedException;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * Data object that have the settings that Filters need to apply
 */
class FilterData implements Contracts\FilterDataInterface, Contracts\ValidatableTraitInterface, Arrayable
{
    use Traits\Validatable;

    /**
     * Validatable filter data: used by ValidatableTrait
     *
     * @var array   associative
     */
    protected $attributes = [];

    /**
     * Validation rules for filter
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Default values. Anything NOT listed here will NOT be applied in queries.
     * Make sure there are defaults for every filterable attribute.
     *
     * @var array   associative
     */
    protected $defaults = [];


    /**
     * Constructor: validate filter data
     *
     * @param array|Arrayable $attributes
     * @param array|Arrayable $defaults     if provided, overrides internal defaults
     * @throws FilterDataValidationFailedException
     */
    public function __construct($attributes, $defaults = null)
    {
        // store attributes as an array
        if (is_a($attributes, Arrayable::class)) {
            $attributes = $attributes->toArray();
        }

        if (empty($attributes)) {
            $attributes = [];
        }

        if ( ! is_array($attributes)) {
            throw new InvalidArgumentException("FilterData constructor parameter was not an array or Arrayable");
        }

        // validate the only the attribute values passed in
        $this->attributes = $attributes;
        $this->validateAttributes();

        // if default overrides are provided, save them
        if ( ! empty($defaults)) {

            if (is_a($defaults, Arrayable::class)) {
                $defaults = $defaults->toArray();
            }

            if ( ! is_array($defaults)) {
                throw new InvalidArgumentException("FilterData constructor parameter for defaults was not an array or Arrayable");
            }

            $this->defaults = $defaults;
        }

        // set attributes, filling in defaults
        $this->attributes = array_merge($this->defaults, $this->attributes);
    }


    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Returns the default values for each applicable attribute
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Gets the attribute names which may be applied
     *
     * @return array
     */
    public function getApplicableAttributes()
    {
        return array_keys($this->defaults);
    }

    /**
     * Validates currently set attributes (not including defaults)
     * against the given validation rules
     *
     * @throws FilterDataValidationFailedException
     */
    protected function validateAttributes()
    {
        if (empty($this->getRules())) return;

        if ( ! $this->validate()) {

            throw (new FilterDataValidationFailedException)->setMessages($this->messages());
        }
    }

    /**
     * Gets the value for a parameter
     *
     * @param $name
     * @return mixed
     */
    public function getParameterValue($name)
    {
        return (isset($this->attributes[$name])) ? $this->attributes[$name] : null;
    }

    /**
     * Getter for attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

}
