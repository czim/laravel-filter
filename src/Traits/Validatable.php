<?php
namespace Czim\Filter\Traits;

use Validator;

/**
 * Allow a class to be validated with validate()
 * use $attributes to set validatable data and
 * use $rules to set the validation rules
 *
 * Note that this requires a getAttributes() method,
 * so use the SimpleGetterSetterHandling trait or
 * set it yourself.
 */
trait Validatable
{
    /**
     * Validator instance
     *
     * @var Validator
     */
    protected $validator = null;


    /**
     * Validates the filter data
     *
     * @return boolean
     */
    public function validate()
    {
        $this->validator = Validator::make($this->getAttributes(), $this->getRules());

        return ! $this->validator->fails();
    }

    /**
     * Returns validation errors, if any
     *
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function messages()
    {
        if (is_null($this->validator)) {
            $this->validate();
        }

        if ( ! $this->validator->fails()) {

            return app('\\Illuminate\\Support\\Messagebag');
        }

        return $this->validator->messages();
    }

    /**
     * Accessor method to check for validation data set
     *
     * @return array
     */
    public function getRules()
    {
        return (isset($this->rules)) ? $this->rules : [];
    }

    /**
     * Setter for $rules
     *
     * @param array $rules
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

}
