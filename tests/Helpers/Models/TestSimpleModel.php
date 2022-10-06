<?php

declare(strict_types=1);

namespace Czim\Filter\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \Eloquent
 */
class TestSimpleModel extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'unique_field',
        'second_field',
        'test_related_model_id',
        'name',
        'position',
        'active',
    ];

    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(TestRelatedModel::class);
    }

    public function relatedModels(): HasMany
    {
        return $this->hasMany(TestRelatedModel::class);
    }
}
