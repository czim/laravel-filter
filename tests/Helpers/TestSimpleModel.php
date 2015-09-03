<?php
namespace Czim\Filter\Test\Helpers;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class TestSimpleModel extends Model
{
    use Translatable;

    protected $fillable = [
        'unique_field',
        'second_field',
        'test_related_model_id',
        'name',
        'active',
    ];

    protected $translatedAttributes = [
        'translated_string',
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
