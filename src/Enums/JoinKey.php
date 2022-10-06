<?php

declare(strict_types=1);

namespace Czim\Filter\Enums;

use MyCLabs\Enum\Enum;

/**
 * Unique identifiers for standard join parameter-sets in filters
 */
class JoinKey extends Enum
{
    public const TRANSLATIONS = 'translations';
    public const PARENT       = 'parent';
    public const CHILDREN     = 'children';
    public const CHILD        = 'child';
}
