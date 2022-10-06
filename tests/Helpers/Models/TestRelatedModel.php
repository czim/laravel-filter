<?php

declare(strict_types=1);

namespace Czim\Filter\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \Eloquent
 */
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

    public function testSimpleModel(): BelongsTo
    {
        return $this->belongsTo(TestSimpleModel::class);
    }

    public function testSimpleModels(): HasMany
    {
        return $this->hasMany(TestSimpleModel::class);
    }
}
