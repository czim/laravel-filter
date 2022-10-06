<?php

declare(strict_types=1);

namespace Czim\Filter\Enums;

use MyCLabs\Enum\Enum;

class JoinType extends Enum
{
    public const INNER = 'join';
    public const LEFT  = 'left';
    public const RIGHT = 'right';
}
