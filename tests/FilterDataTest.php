<?php
namespace Czim\Filter\Test;

use Czim\Filter\Contracts\FilterDataInterface;
use Czim\Filter\Test\Helpers\TestFilterData;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FilterDataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function seedDatabase()
    {
        // don't need this
    }


    // --------------------------------------------
    //      Instantiation / Init
    // --------------------------------------------

    /**
     * @test
     */
    function it_can_be_instantiated_with_array_data()
    {
        $this->assertInstanceOf(
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
    function it_can_be_instantiated_with_arrayble_data()
    {
        $arrayable = new Collection([
            'name'     => 'some name',
            'relateds' => [1, 2, 3],
        ]);

        $this->assertInstanceOf(FilterDataInterface::class, new TestFilterData($arrayable));
    }

    /**
     * @test
     * @expectedException \Czim\Filter\Exceptions\FilterDataValidationFailedException
     */
    function it_throws_an_exception_if_invalid_data_is_passed_in()
    {

        // see if we get the messages correctly
        try {
            new TestFilterData([
                'name'          => 'some name',
                'relateds'      => 'string which should be an array',
                'position'      => 'string which should be an integer',
                'with_inactive' => 'not even a boolean here',
            ]);
        } catch (\Czim\Filter\Exceptions\FilterDataValidationFailedException $e) {

            $messages = $e->getMessages();

            $this->assertInstanceOf(MessageBag::class, $messages, "Exception getMessages is not a MessageBag");
            $this->assertCount(3, $messages, "Exception getMessages should have 3 messages");
        }

        // throw the exception, but don't catch it this time
        new TestFilterData([
            'name'          => 'some name',
            'relateds'      => 'string which should be an array',
            'position'      => 'string which should be an integer',
            'with_inactive' => 'not even a boolean here',
        ]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_if_constructor_parameter_is_not_an_array_or_arrayable()
    {
        new TestFilterData(50 + 3932);
    }

    // --------------------------------------------
    //      Getters, setters and defaults
    // --------------------------------------------


    /**
     * @test
     */
    function it_sets_default_values_for_parameters_not_provided()
    {
        $data = new TestFilterData([
            'name' => 'some name',
        ]);

        // check default
        $this->assertSame(false, $data->getDefaults()['with_inactive'], "Defaults not correct for test");

        // check whether default was set
        $this->assertSame(false, $data->getParameterValue('with_inactive'), "Defaults were not set for parametervalues");
    }

    /**
     * @test
     */
    function it_accepts_custom_defaults_through_constructor_parameter()
    {
        $data = new TestFilterData(
            [ 'name' => 'some name' ],
            // custom defaults
            [
                'name'          => null,
                'with_inactive' => true,
            ]
        );

        // check default
        $this->assertSame(true, $data->getDefaults()['with_inactive'], "Defaults not correct for test");

        // check whether default was set
        $this->assertSame(true, $data->getParameterValue('with_inactive'), "Defaults were not set for parametervalues");
    }

}
