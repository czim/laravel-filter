<?php

namespace Czim\Filter\Contracts;

use Illuminate\Contracts\Support\MessageBag;

interface ValidatableTraitInterface
{
    public function validate(): bool;

    /**
     * If validation tried and failed, returns validation messages.
     *
     * @return MessageBag
     */
    public function messages(): MessageBag;

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array;

    /**
     * @param array<string, mixed> $rules
     */
    public function setRules(array $rules): void;

}
