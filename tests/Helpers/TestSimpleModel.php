<?php

namespace Czim\Filter\Test\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function RelatedModel(): BelongsTo
    {
        return $this->belongsTo(TestRelatedModel::class);
    }

    public function RelatedModels(): HasMany
    {
        return $this->hasMany(TestRelatedModel::class);
    }
}
