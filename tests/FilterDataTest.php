<?php

namespace Czim\Filter\Test;

use Czim\Filter\Contracts\FilterDataInterface;
use Czim\Filter\Exceptions\FilterDataValidationFailedException;
use Czim\Filter\Test\Helpers\TestFilterData;

class FilterDataTest extends TestCase
{
    // --------------------------------------------
    //      Instantiation / Init
    // --------------------------------------------

    /**
     * @test
     */
    public function it_can_be_instantiated_with_array_data(): void
    {
        static::assertInstanceOf(
            FilterDataInterface::class,
            new TestFilterData([
                'name'          => 'some name',
                'relateds'      => [1, 2, 3],
                'position'      => 20,
                'with_inactive' => false,
            ])
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_invalid_data_is_passed_in(): void
    {
        // See if we get the messages correctly.
        try {
            new TestFilterData([
                'name'          => 'some name',
                'relateds'      => 'string which should be an array',
                'position'      => 'string which should be an integer',
                'with_inactive' => 'not even a boolean here',
            ]);
        } catch (FilterDataValidationFailedException $e) {
            $messages = $e->getMessages();
            static::assertCount(3, $messages, "Exception getMessages should have 3 messages");
        }

        $this->expectException(FilterDataValidationFailedException::class);

        // Throw the exception, but don't catch it this time.
        new TestFilterData([
            'name'          => 'some name',
            'relateds'      => 'string which should be an array',
            'position'      => 'string which should be an integer',
            'with_inactive' => 'not even a boolean here',
        ]);
    }

    // --------------------------------------------
    //      Getters, setters and defaults
    // --------------------------------------------

    /**
     * @test
     */
    public function it_sets_default_values_for_parameters_not_provided(): void
    {
        $data = new TestFilterData([
            'name' => 'some name',
        ]);

        static::assertFalse($data->getDefaults()['with_inactive'], 'Defaults not correct for test');

        static::assertFalse($data->getParameterValue('with_inactive'), 'Defaults were not set for parametervalues');
    }

    /**
     * @test
     */
    public function it_accepts_custom_defaults_through_constructor_parameter(): void
    {
        $data = new TestFilterData(
            [
                'name' => 'some name',
            ],
            // Custom defaults:
            [
                'name'          => null,
                'with_inactive' => true,
            ]
        );

        static::assertTrue($data->getDefaults()['with_inactive'], 'Defaults not correct for test');

        static::assertTrue($data->getParameterValue('with_inactive'), 'Defaults were not set for parametervalues');
    }
}
