<?php

namespace Czim\Filter;

use Czim\Filter\Exceptions\FilterDataValidationFailedException;

/**
 * Data object that have the settings that Filters need to apply.
 */
class FilterData implements Contracts\FilterDataInterface, Contracts\ValidatableTraitInterface
{
    use Traits\Validatable;

    /**
     * Validatable filter data: used by ValidatableTrait.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [];

    /**
     * Validation rules for filter.
     *
     * @var array<string, mixed>
     */
    protected $rules = [];

    /**
     * Default values. Anything NOT listed here will NOT be applied in queries.
     * Make sure there are defaults for every filterable attribute.
     *
     * @var array<string, mixed>
     */
    protected $defaults = [];


    /**
     * Constructor: validate filter data.
     *
     * @param array<string, mixed>      $attributes
     * @param array<string, mixed>|null $defaults   if provided, overrides internal defaults
     * @throws FilterDataValidationFailedException
     */
    public function __construct(array $attributes, ?array $defaults = null)
    {
        // Validate and sanitize the attribute values passed in.
        $this->attributes = $this->sanitizeAttributes($attributes);

        $this->validateAttributes();

        if ($defaults !== null) {
            $this->defaults = $defaults;
        }

        // Set attributes, filling in defaults.
        $this->attributes = array_merge($this->defaults, $this->attributes);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Gets the attribute names which may be applied.
     *
     * @return string[]
     */
    public function getApplicableAttributes(): array
    {
        return array_keys($this->defaults);
    }

    /**
     * Sanitizes the attributes passed in.
     *
     * Override this to apply sanitization to any attributes passed into the class.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    protected function sanitizeAttributes(array $attributes): array
    {
        return $attributes;
    }

    /**
     * Validates currently set attributes (not including defaults)
     * against the given validation rules.
     *
     * @throws FilterDataValidationFailedException
     */
    protected function validateAttributes(): void
    {
        if (empty($this->getRules())) {
            return;
        }

        if (! $this->validate()) {
            throw (new FilterDataValidationFailedException)->setMessages($this->messages());
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getParameterValue(string $name)
    {
        return $this->attributes[ $name ] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
