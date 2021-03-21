<?php

namespace Czim\Filter\Test\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestRelatedModel extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'some_property',
        'test_simple_model_id',
        'active',
    ];

    // some relations for testing

    public function TestSimpleModel(): BelongsTo
    {
        return $this->belongsTo(TestSimpleModel::class);
    }

    public function TestSimpleModels(): HasMany
    {
        return $this->hasMany(TestSimpleModel::class);
    }
}
