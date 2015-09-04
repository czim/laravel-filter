# Laravel Filter

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Latest Stable Version](http://img.shields.io/packagist/v/czim/laravel-filter.svg)](https://packagist.org/packages/czim/laravel-filter)

Configurable and modular Filter setup for Laravel.
This is intended to make it easy to search for and filter by records using a typical web shop filter.
For example, if you want to filter a catalog of products by product attributes, brand names, product lines and so forth.

The standard Filter class provided is set up to apply filters to a given (Eloquent) query builder.
Additionally a CountableFilter extension of the class is provided for offering typical counts for determining what alternative filter settings should be displayed to visitors.

This is not a ready-to-use package, but a framework you can extend for your own specific applications.

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


## Filter Data

You may pass any array or Arrayable data directly into the filter, and it will create a `FilterData` object for you.
If you do not have the `$filterDataClass` property overridden, however, your filter will do nothing (because no attributes and defaults are set for it, the FilterData will always be empty).
In you extension of the `Filter` class, override the property like so in order to be able to let the Filter create it automatically:

``` php
    class YourFilter extends \Czim\Filter
    {
        protected $filterDataClass = \Your\FilterDataClass::class;
        
        ...
    }
```

Your `FilterData` class should then look something like this:

``` php
    class FilterDataClass extends \Czim\FilterData
    {
        // Validation rules for filter attributes passed in
        protected $rules = [
            'name'   => 'string|required',
            'brands' => 'array|each:integer',
            'before' => 'date',
            'active' => 'boolean',
        ];
        
        // Default values and the parameter names accessible to the Filter class
        // If (optional) filter attributes are not provided, these defaults will be used:
        protected $defaults = [
            'name'   => null,
            'brands' => [],
            'before' => null,
            'active' => true,
        ];
    }
```

Filter validation rules are optional. If no rules are provided, validation always passes.
Defaults are *required*, and define which parameter keys are applied by the filter.

Then, passing array(able) data into the constructor of your filter will automatically instantiate that FilterData class for you.
If it is an (unmodified) extension of `Czim\FilterData`, it will also validate the data and throw an exception if the data does not match the `$rules` defined in your Data class.

Alternatively, you can make your own implementation of the provided `FilterDataInterface` and pass it into the Filter directly.

``` php
    $filter = new YourFilter( new YourFilterData($someData) );
```

All it needs to do is implement the interface; if you pass in data this way, the data will be set without any further checks or validation, unless you handle it in your FilterData implementation yourself.


## Filters

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

*Important*: filter logic is only invoked if the parameter's provided value is **not empty**.
Regardless of the method you choose to make your filter application, it will *only* be applied if: `! empty($value) || $value === false`. 


### Strategies and ParameterFilters

You can define strategies for each filter parameter by adding a strategies method to your filter as follows:

``` php
    protected function strategies()
    {
        return [
            // as a ParameterFilter instance
            'parameter_name_here' => new \Czim\Filter\ParameterFilters\SimpleString(),
            
            // as a ParameterFilter class string
            'another_parameter'   => \Czim\Filter\ParameterFilters\SimpleString::class,
            
            // as an anonymous function
            'yet_another'         => function($name, $value, $query) {
                                        return $query->where('some_column', '>', $value);
                                     },
                                     
            // as an array (passable to call_user_func()) 
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

Some common ParameterFilters are included in this package:

* `SimpleInteger`: for looking up (integer) values with an optional operator ('=' by default)
* `SimpleString`: for looking up string values, with a *LIKE % + value + %* match by default
* `SimpleTranslatedString`: (uses `JoinKey::Translations` as the join key)


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


## Countable Filters

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


### Counting Strategies

Strategies may be defined for the effects of `count()` per parameter for your CountableFilter, in the same way as normal filter strategies.

``` php
    protected function countStrategies()
    {
        return [
            'parameter_name_here' => new \Czim\Filter\ParameterCounters\SimpleInteger(),
            ...
        ];
    }
```

The same methods for defining strategies are available as with the `strategies()` methods above: instances (of ParameterCounters in this case), strings, closures and arrays. 


### ParameterCounters

Just like ParameterFilters for `Filter`, ParameterCounters can be used as 'plugins' for your `CountableFilter`.

``` php
    
    protected function countStrategies()
    {
        return [
            'parameter_name_here' => new ParameterCounters\YourParameterCounter()
        ];
    }
```

ParameterCounters must implement the `ParameterCounterInterface`, featuring this method:

``` php
    /**
     * @param string                   $name
     * @param EloquentBuilder          $query
     * @param CountableFilterInterface $filter
     */
    public function count($name, $query, CountableFilterInterface $filter);
```

## Settings and Extra stuff

### Joins

When joining tables for filter parameters, it may occur that different parameters require the same join(s).
In order to prevent duplicate joining of tables, the Filter class has a built in helper for working with joins.

``` php
    // within your applyParameter implementation
    // the first parameter is a keyname you define, see the JoinKey enum provided
    // the second parameter is an array of parameters that is passed on directly
    //     to Laravel's query builder join() method.
    $this->addJoin('keyname_for_your_join', [ 'table', 'column_a', '=', 'column_b' ]);
    
    // or within a ParameterFilter apply() method, call it on the filter
    $filter->addJoin( ... , [ ... ]);
```

Joins so added are automatically applied to the filter after all parameters are applied.
 

### Global Filter Settings

Sometimes it may be useful to let filter-wide settings affect the way your filter works.
You can set these through a setter directly on the filter class, `setSetting()`.
Alternatively, you can define a filter parameter strategy as `Filter::SETTING`, and it will be loaded as a setting before the filter is applied.

``` php
    // in your Filter class:
    protected function strategies()
    {
        return [
            ...
            
            'global_setting_name' => static::SETTING
        ];
    }
```

When a setting has been set in either way, you can check it with the `setting()` method.
Note that the ParameterFilter/ParameterCounter also receives the `$filter` itself as a parameter and the method is public. 

If a setting has not been defined, the `setting()` method for it will return `null`.


## Examples

To Do: add some example code in a separate .md file
include full classes for Filter and CountableFilter


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
