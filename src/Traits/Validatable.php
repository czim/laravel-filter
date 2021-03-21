<?php

namespace Czim\Filter\Traits;

use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Support\MessageBag;
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
     * @var Validator|null
     */
    protected $validator;


    public function validate(): bool
    {
        $this->validator = Validator::make($this->getAttributes(), $this->getRules());

        return ! $this->validator->fails();
    }

    public function messages(): MessageBagContract
    {
        if ($this->validator === null) {
            $this->validate();
        }

        if (! $this->validator->fails()) {
            return app(MessageBag::class);
        }

        return $this->validator->messages();
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        if (isset($this->rules)) {
            return $this->rules;
        }

        return [];
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }
}
