<?php

namespace Czim\Filter\Contracts;

use Illuminate\Contracts\Support\MessageBag;

interface ValidatableTraitInterface
{
    public function validate(): bool;

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
