<?php
namespace Czim\Filter\Contracts;

use Czim\Filter\CountableResults;

interface CountableFilterInterface extends FilterInterface
{
    /**
     * Gets alternative counts per (relevant) attribute for the filter data.
     *
     * @return CountableResults
     */
    public function getCounts();
}
