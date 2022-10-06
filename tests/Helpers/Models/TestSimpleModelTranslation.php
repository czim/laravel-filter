<?php

declare(strict_types=1);

namespace Czim\Filter\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 */
class TestSimpleModelTranslation extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'translated_string',
    ];
}
