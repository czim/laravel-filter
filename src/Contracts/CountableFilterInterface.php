<?php
namespace Czim\Filter\Contracts;

use Czim\Filter\CountableResults;

interface CountableFilterInterface extends FilterInterface
{
    /**
     * Gets alternative counts per (relevant) attribute for the filter data.
     *
     * @param array $countables     if provided, limits the result to theses countables
     * @return CountableResults
     */
    public function getCounts($countables = []);

    /**
     * Disables one or more countables when getCounts() is invoked
     *
     * @param string|array $countable
     * @return $this
     */
    public function ignoreCountable($countable);

    /**
     * Re-enables one or more countables when getCounts() is invoked
     *
     * @param string|array $countable
     * @return $this
     */
    public function unignoreCountable($countable);

}
