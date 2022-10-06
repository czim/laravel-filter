<?php

namespace Czim\Filter\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface FilterDataInterface extends Arrayable
{
    /**
     * Returns the default values for each applicable attribute.
     *
     * @return array<string, mixed>
     */
    public function getDefaults(): array;

    /**
     * Gets the attribute names which may be applied.
     *
     * @return string[]
     */
    public function getApplicableAttributes(): array;

    public function getParameterValue(string $name): mixed;

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array;
}
