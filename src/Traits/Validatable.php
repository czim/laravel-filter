<?php

declare(strict_types=1);

namespace Czim\Filter\Traits;

use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use UnexpectedValueException;

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
    protected ValidatorContract $validator;


    public function validate(): bool
    {
        $this->validator = $this->makeValidatorInstance();

        return ! $this->validator->fails();
    }

    protected function makeValidatorInstance(): ValidatorContract
    {
        return ValidatorFacade::make($this->getAttributes(), $this->getRules());
    }

    public function messages(): MessageBagContract
    {
        if (! isset($this->validator)) {
            $this->validate();
        }

        if ($this->validator->fails()) {
            return $this->validator->messages();
        }

        return $this->makeEmptyMessageBag();
    }

    protected function makeEmptyMessageBag(): MessageBagContract
    {
        return new MessageBag();
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        if (! property_exists($this, 'rules')) {
            return [];
        }

        return $this->rules ?? [];
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function setRules(array $rules): void
    {
        if (! property_exists($this, 'rules')) {
            // Don't allow dynamic property assignment anymore.
            throw new UnexpectedValueException('No rules property available to set rules on');
        }

        $this->rules = $rules;
    }
}
