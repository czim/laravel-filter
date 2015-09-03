<?php
namespace Czim\Filter\Contracts;

use Illuminate\Contracts\Support\MessageBag;

interface ValidatableTraitInterface
{
    /**
     * Validate attributes
     *
     * @return boolean
     */
    public function validate();

    /**
     * If validation tried and failed, returns validation messages
     *
     * @return MessageBag
     */
    public function messages();

    /**
     * Returns currently set validation rules
     *
     * @return array
     */
    public function getRules();

    /**
     * Sets validation rules
     *
     * @param array $rules
     * @return void
     */
    public function setRules(array $rules);

}
