<?php
namespace Czim\Filter\Contracts;

use Czim\Filter\CountableResults;

interface CountableFilterInterface
{
    /**
     * Gets alternative counts per (relevant) attribute for the filter data.
     *
     * @return CountableResults
     */
    public function getCounts();
}
