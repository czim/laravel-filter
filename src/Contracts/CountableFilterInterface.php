<?php

namespace Czim\Filter\Contracts;

use Czim\Filter\CountableResults;

interface CountableFilterInterface extends FilterInterface
{
    /**
     * Returns a list of the countable parameters to get counts for.
     *
     * @return array<string, mixed>
     */
    public function getCountables(): array;

    /**
     * Gets alternative counts per (relevant) attribute for the filter data.
     *
     * @param string[] $countables when provided, limits the result to theses countables
     * @return CountableResults
     */
    public function getCounts(array $countables = []): CountableResults;

    /**
     * Disables a countable when getCounts() is invoked.
     *
     * @param string $countable
     */
    public function ignoreCountable(string $countable): void;

    /**
     * Disables a number of countables when getCounts() is invoked.
     *
     * @param string[] $countables
     */
    public function ignoreCountables(array $countables): void;

    /**
     * Re-enables a countable when getCounts() is invoked.
     *
     * @param string $countable
     */
    public function unignoreCountable(string $countable): void;

    /**
     * Re-enables a number of countables when getCounts() is invoked.
     *
     * @param string[] $countables
     */
    public function unignoreCountables(array $countables): void;

    /**
     * Returns whether a given countable is currently being ignored/omitted
     *
     * @param string $countableName
     * @return bool
     */
    public function isCountableIgnored(string $countableName): bool;
}
