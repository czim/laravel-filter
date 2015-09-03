<?php
namespace Czim\Filter\Enums;

use PHPExtra\Type\Enum\Enum;

/**
 * Unique identifiers for standard join parameter-sets in filters
 */
class JoinKey extends Enum
{
    const _default = '';

    const Translations = 'translations';
    const Parent       = 'parent';
    const Children     = 'children';
    const Child        = 'child';
}
