<?php

declare(strict_types=1);

namespace Czim\Filter\Test;

use Czim\Filter\Contracts\CountableFilterInterface;
use Czim\Filter\Test\Helpers\Models\TestRelatedModel;
use Czim\Filter\Test\Helpers\Models\TestSimpleModel;
use Czim\Filter\Test\Helpers\TestCountableFilter;

class CountableFilterTest extends TestCase
{
    protected const TABLE_NAME   = 'test_simple_models';
    protected const UNIQUE_FIELD = 'unique_field';
    protected const SECOND_FIELD = 'second_field';

    protected function seedDatabase(): void
    {
        TestSimpleModel::create([
            'unique_field'          => '11',
            'second_field'          => null,
            'name'                  => 'simple name',
            'test_related_model_id' => 1,
            'position'              => 0,
            'active'                => true,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '123',
            'second_field'          => 'random string',
            'name'                  => 'random name',
            'test_related_model_id' => 2,
            'position'              => 1,
            'active'                => true,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '1337',
            'second_field'          => 'some more',
            'name'                  => 'special name',
            'test_related_model_id' => 3,
            'position'              => 14,
            'active'                => true,
        ]);

        TestSimpleModel::create([
            'unique_field'          => '1980',
            'second_field'          => 'yet more fun',
            'name'                  => 'another name',
            'test_related_model_id' => 1,
            'position'              => 14,
            'active'                => true,
        ]);

        TestRelatedModel::create([
            'name'                 => 'related A',
            'some_property'        => 'super',
            'test_simple_model_id' => 3,
            'active'               => true,
        ]);

        TestRelatedModel::create([
            'name'                 => 'related B',
            'some_property'        => 'generic',
            'test_simple_model_id' => 3,
            'active'               => true,
        ]);

        TestRelatedModel::create([
            'name'                 => 'related C',
            'some_property'        => 'mild',
            'test_simple_model_id' => 2,
            'active'               => true,
        ]);
    }


    // --------------------------------------------
    //      Instantiation / Init
    // --------------------------------------------

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        static::assertInstanceOf(
            CountableFilterInterface::class,
            new TestCountableFilter([])
        );

        static::assertInstanceOf(
            CountableFilterInterface::class,
            new TestCountableFilter(['name' => 'test'])
        );
    }


    /**
     * @test
     */
    public function it_returns_list_of_countable_parameter_names(): void
    {
        $filter = new TestCountableFilter([]);

        static::assertCount(
            2,
            $filter->getCountables(),
            'Wrong count for getCountables()'
        );
    }


    // --------------------------------------------
    //      getCounts
    // --------------------------------------------

    /**
     * @test
     */
    public function it_returns_correct_counts(): void
    {
        $filter = new TestCountableFilter([]);

        $counts = $filter->getCounts();

        static::assertCount(2, $counts, 'getCounts() results should have 2 items');

        // Position 14 appears twice, 0 and 1 once.
        static::assertEquals(
            [0 => 1, 1 => 1, 14 => 2],
            $counts->get('position')->toArray(),
            'getCounts() first (distinct value) results incorrect'
        );

        // Related model id 1 appears twice, the rest once.
        static::assertEquals(
            [1 => 2, 2 => 1, 3 => 1],
            $counts->get('relateds')->toArray(),
            'getCounts() first (belongsto) results incorrect'
        );
    }


    // --------------------------------------------
    //      ignored countables
    // --------------------------------------------

    /**
     * @test
     */
    public function it_returns_only_unignored_countable_results(): void
    {
        $filter = new TestCountableFilter([]);

        $filter->ignoreCountable('relateds');

        $counts = $filter->getCounts();

        static::assertCount(1, $counts, 'getCounts() results should have 1 item (the other is ignored)');

        // Position 14 appears twice, 0 and 1 once.
        static::assertEquals(
            [0 => 1, 1 => 1, 14 => 2],
            $counts->get('position')->toArray(),
            'getCounts() result should be correct position only'
        );


        // After unignoring, all countables should be there.
        $filter->unignoreCountable('relateds');

        $counts = $filter->getCounts();

        static::assertCount(2, $counts, 'getCounts() results should have 2 items after unignoring countable');
    }

    /**
     * @test
     */
    public function it_returns_counts_for_selected_keys_only(): void
    {
        $filter = new TestCountableFilter([]);

        $counts = $filter->getCounts(['position']);

        static::assertCount(1, $counts, 'getCounts() results should have 1 item (the other is ignored)');

        // Position 14 appears twice, 0 and 1 once.
        static::assertEquals(
            [0 => 1, 1 => 1, 14 => 2],
            $counts->get('position')->toArray(),
            'getCounts() result should be correct position only'
        );
    }
}
