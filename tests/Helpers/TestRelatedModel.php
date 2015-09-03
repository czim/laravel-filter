<?php
namespace Czim\Filter\Test\Helpers;

use Illuminate\Database\Eloquent\Model;

class TestRelatedModel extends Model
{
    protected $fillable = [
        'name',
        'some_property',
        'test_simple_model_id',
        'active',
    ];

    // some relations for testing

    public function TestSimpleModel()
    {
        return $this->belongsTo(TestSimpleModel::class);
    }

    public function TestSimpleModels()
    {
        return $this->hasMany(TestSimpleModel::class);
    }
}
