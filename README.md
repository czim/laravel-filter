# Laravel Filter

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Latest Stable Version](http://img.shields.io/packagist/v/czim/laravel-filter.svg)](https://packagist.org/packages/czim/laravel-filter)

Configurable and modular Filter setup for Laravel. This is intended to make it easy to search for and filter by records using a typical web shop filter.
For example, if you want to filter a catalog of products by product attributes, brand names, product lines and so forth.

The standard Filter class provided is set up to apply filters to a given (Eloquent) query builder. Additionally a CountableFilter extension of the class is provided for offering typical counts for determining what alternative filter settings should be displayed to visitors.


## Install

Via Composer

``` bash
$ composer require czim/laravel-filter
```

If you run into problems with `phpextra/enum`, please run its installation separately beforehand:

``` bash
$ composer require phpextra/enum 'dev-master'
```


## Basic Usage

Make a class that extends `Czim\FilterData` and set the protected propeties for validation rules `$rules` and the default values for these attributes `$defaults`.
Note that `$defaults` are used as the main means to detect which filter parameters need to be applied to the query, so make sure all filter parameters you want to implement are present in it.

Simply extend the (abstract) filter class of your choice, either `Czim\Filter\Filter` or `Czim\Filter\CountableFilter`.

Each has abstract methods that must be provided for the class to work. Once they are all set (see below), you can simply apply filter settings to a query:
 
``` php
    $filterValues = [ 'attributename' => 'value', ... ];

    $filter = new SomeFilter($filterValues);
    
    // get an Eloquent builder query for a model
    $query = SomeEloquentModel::query();
    
    // apply the filter to the query
    $filteredQuery = $filter->apply($query);
    
    // normal get() call on the now filtered query
    $results = $filteredQuery->get();
```

A `CountableFilter` has an additional method that may be called:

``` php
    $countResults = $filter->count();
```

You can find more about countable filters below.


### Filter Data

You may pass any array or Arrayable data directly into the filter, and it will create a `FilterData` object for you.
If you do not have the `$filterDataClass` property overridden, however, your filter will do nothing (because no attributes and defaults are set for it, the FilterData will always be empty).
In you extension of the `Filter` class, override the property like so in order to be able to let the Filter create it automatically:

    class YourFilter extends \Czim\Filter
    {
        protected $filterDataClass = \Your\FilterDataClass::class;
        
        ...
    }

Your `FilterData` class should then look something like this:

``` php
    class FilterDataClass extends \Czim\FilterData
    {
        // Validation rules for filter attributes passed in
        protected $rules = [
            'name'   => 'string|required',
            'brands' => 'array|each:integer',
            'value'  => 'integer',
            'active' => 'boolean',
        ];
        
        // Default values and the parameter names accessible to the Filter class
        // If (optional) filter attributes are not provided, these defaults will be used:
        protected $defaults = [
            'name'   => null,
            'brands' => [],
            'value'  => 0,
            'active' => true,
        ];
    }
```

Then, passing array(able) data into the constructor of your filter will automatically instantiate that FilterData class for you.
If it is an (unmodified) extension of `Czim\FilterData`, it will also validate the data and throw an exception if the data does not match the `$rules` defined in your Data class.

Alternatively, you can make your own implementation of the provided `FilterDataInterface` and pass it into the Filter directly.

``` php
    $filter = new YourFilter( new YourFilterData($someData) );
```

All it needs to do is implement the interface; if you pass in data this way, the data will be set without any further checks or validation, unless you handle it in your FilterData implementation yourself.


### Filters

Basic Filters take a query and apply filter parameters to it, before handing it back.
(Note that the query object passed in will be modified; it is not cloned in the Filter before making modifications).

For example, if you'd do the following:

``` php
    $query = SomeModel::where('some_column', 1);
     
    $query = (new YourFilter([ 'name' => 'random' ])->apply($query);
     
    echo $query->toSql();
```

You might expect the result to be something like `select * from some_models where some_column = 1 and name LIKE '%random%'`.

What a filter exactly does with the filter data you pass into its constructor must be defined in your implementation.
This may be done in two main ways, which can be freely combined:

*   By defining *strategies* (overriding the public `strategies()` method)
*   By overriding the `applyParameter()` method as a fallback option

### Strategies and ParameterFilters

You can define strategies for each filter parameter by adding a strategies method to your filter as follows:

``` php
    protected function strategies()
    {
        return [
            'parameter_name_here' => new \Czim\Filter\ParameterFilters\SimpleString(),
            'another_parameter'   => \Czim\Filter\ParameterFilters\SimpleString::class,
            'yet_another'         => function($name, $value, $query) {
                                        return $query->where('some_column', '>', $value);
                                     },
            'and_another'         => [ $this, 'someMethodYouDefined' ],
        ];
    }
```

If filter data is passed into the class with the same keyname as a strategy, that strategy method will be invoked.
As shown above, there are different ways to provide a callable method for filters, but all methods mean passing data to a function that takes these parameters:

``` php
    /**
     * @param string          $name     the keyname of the parameter/strategy
     * @param mixed           $value    the value for this parameter set in the filter data
     * @param EloquentBuilder $query
     * @param FilterInterface $filter   the filter from which the strategy was invoked
     */
    public function apply($name, $value, $query);
```

A `ParameterFilter` is a class (any that implements the `ParameterFilterInterface`) which may be set as a filter strategy.
The `apply()` method on this class will be called when the filter is applied.
If the ParameterFilter is given as a string for the strategy, it will be instantiated at the moment the filter is applied.

Strategies may also be defined as closures or arrays (so long as they may be fed into a `call_user_func()`).
The method called by this will receive the four parameters noted above.
 
Only if no strategy has been defined for a parameter, the callback method `applyParameter()` will be called on the filter itself.
By default, an exception will occur.


### The fallback option: applyParameter()

If you prefer, you can also use the fallback method to handle any or all of the appliccable parameters.
Simply add the following method to your filter class:

``` php
    protected function applyParameter($name, $value, $query)
    {
        switch ($name) {
        
            case 'parameter_name_here':
                
                // your implementation of the filter ...
                return $query;
                
            ...
        }
        
        // as a safeguard, you can call the parent method,
        // which will throw exceptions for unhandled parameters
        parent::applyParameter($name, $value, $query);
    }
```

You can freely combine this approach with the strategy definitions mentioned above.
The only limitation is that when there is a strategy defined for a parameter, the `applyParameter()` fallback will not be called for it.


### Countable Filters

The `CountableFilter` is an extension of the normal filter that helps write special filters for, say, web shops where it makes sense to show relevant alternatives based on the current filter choices.

Take a product catalog, for instance, where you're filtering based on a particular brand name and a price range.
In the filter options shown, you may want to display other brands that your visitor can filter on, but *only* the brands for which your have products in the chosen price range.
The idea is to prevent your visitors from selecting a different brand only to find that there are no results.

`CountableFilters` help you to do this, by using currently set filters to generate counts for alternative options.
Say you have brand X, Y and Z, and are filtering products only for brand X and only in a given price range.
The countable filter makes it easy to get a list of how many products also have matches for the price range of brand Y and Z.
  
To set up a `CountableFilter`, set up the `Filter` as normal, but additionally configure `$countables` and `countStrategies()`.
The counting strategies are similarly configurable/implementable as filtering strategies. 

The return value for `CountableFilter::count()` is an instance of `Czim\CountableResults`, which is basically a standard Laravel `Collection` instance.




### ParameterCounters

Just like ParameterFilters for `Filter`, ParameterCounters can be used as 'plugins' for your `CountableFilter`.


### Global Filter Settings


## Examples

To Do: add some example code in a separate .md file
include full classes for Filter and CountableFilter

## Configuration
No configuration is required to start using the filter. You use it by extending an abstract filter class of your choice. 


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Coen Zimmerman][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/laravel-filter.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/laravel-filter.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/laravel-filter
[link-downloads]: https://packagist.org/packages/czim/laravel-filter
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
