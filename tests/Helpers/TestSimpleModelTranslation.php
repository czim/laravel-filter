<?php

namespace Czim\Filter\Test\Helpers;

use Illuminate\Database\Eloquent\Model;

class TestSimpleModelTranslation extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'translated_string',
    ];
}
