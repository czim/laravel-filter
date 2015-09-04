<?php
namespace Czim\Filter\Test\Helpers;

use Illuminate\Database\Eloquent\Model;

class TestSimpleModel extends Model
{
    protected $fillable = [
        'unique_field',
        'second_field',
        'test_related_model_id',
        'name',
        'position',
        'active',
    ];

    public function RelatedModel()
    {
        return $this->belongsTo(TestRelatedModel::class);
    }

    public function RelatedModels()
    {
        return $this->hasMany(TestRelatedModel::class);
    }
}
