# Examples

## Filter

The following is a hypothetical situation where a product catalog should be filterable on common qualities of products.

What we want to be able to do is pass in the following array to a filter to get the products that match:

```php
    [
        'name'       => '',   // a string to loosely match product names by
        'ean'        => '',   // a string to exactly match product EAN codes by
        'products'   => [],   // an array of product id integers
        'brands'     => [],   // an array of brand id integers to which the product must belong
        'categories' => [],   // an array of category id integers to which the product must belong
    ]
```

So that, for instance, to find all products that have 'enhanced' in their name, you would provide:
 
```php
     [
         'name' => 'enhanced',
     ]
 ```
 
 Or to find all products belonging *either* to the category with id #3 *and/or* the categoy with id #4:
 
 ```php
      [
          'categories' => [ 3, 4 ],
      ]
  ```


### Data structure

Let's say we have the following:

- A `products` table with very basic product information, a name, EAN code and description. Model: *Product*.
- A `brands` table. A *Product* has one *Brand*.
- A `categories` table. A *Product* can belong to zero or more *Categories*.
  The pivot table follows the Laravel convention: `category_product`.

For details, see the [migrations and models](EXAMPLE_DETAILS.md) for this setup.

### Filter Data

```php
<?php
namespace App\Filters;

use Czim\Filter\FilterData;

class ProductData extends FilterData
{
    protected $rules = [
        'name'       => 'string|max:255',
        'ean'        => 'string|max:20',
        'products'   => 'array|each:exists,products,id',
        'brands'     => 'array|each:exists,brands,id',
        'categories' => 'array|each:exists,categories,id',
    ];

    protected $defaults = [
        'name'       => null,
        'ean'        => null,
        'products'   => [],
        'brands'     => [],
        'categories' => [],
    ];
}

```

### Filter Class

```php
<?php
namespace App\Filters;

use Czim\Filter\Filter;

use Czim\Filter\ParameterCounters;
use Czim\Filter\ParameterFilters;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;


class ProductFilter extends Filter
{
    protected $table = 'products';

    protected $filterDataClass = ProductData::class;


    protected function strategies()
    {
        return [
            // Loosy string match
            'name'     => new ParameterFilters\SimpleString($this->table),
            // Exact string match
            'ean'      => new ParameterFilters\SimpleString($this->table, null, true),
            // simple integer column id matches
            'products' => new ParameterFilters\SimpleInteger($this->table, 'id'),
            'brands'   => new ParameterFilters\SimpleInteger($this->table, 'brand_id'),
        ];

        // Note that 'categories' is not present here,
        // so it will have to be handled in the applyParameter method.
    }


    /**
     * @param string          $name
     * @param mixed           $value
     * @param EloquentBuilder $query
     */
    protected function applyParameter($name, $value, $query)
    {
        switch ($name) {

            // Categories requires a special implementation, it needs to join a pivot table.
            // This could have also been implemented as a custom ParameterFilter class,
            // but adding it to the applyParameter method works too.

            case 'categories':

                // The addJoin method will let the Filter add the join statements to the
                // query builder when all filter parameters are applied.
                // If you were to call addJoin with the same ('category_product') key name
                // again, it would only be added to the query once.
                
                $this->addJoin('category_product', [
                    'category_product',
                    'category_product.product_id', '=', 'products.id'
                ]);

                $query->whereIn('category_product.product_category_id', $value)
                      ->distinct(); // Might have multiple matches per product
                return;
        }

        // fallback to default: throws exception for unhandled filter parameter
        parent::applyParameter($name, $value, $query);
    }

}
```

## CountableFilter

It might make sense to make this ProductFilter into a CountableFilter, which can return counts for `brands` and `categories`.
For instance, you would pass in as filter data the following:

 ```php
      [
          'categories' => [ 3, 4 ],
      ]
  ```

And receive the alternative counts by calling `getCountables()` on the filter:

```php
    // the toArray() of the CountResult returned: 
    [
        'brands' => [
            1 => 2,     // For products belonging to either Category #3 or #4, 
            2 => 1,     // there are two Products for Brand #1, one for #2
            4 => 10,    // and ten for Brand #4. None for #3 or any other, in this case.
        ],
        'categories' => [
            1 => 5,     // These counts are the results for all products,
            2 => 3,     // since no other filter parameters are active but
            3 => 11,    // the one on categories. So this list gives the product
            4 => 8,     // counts for when the categories filter would not be applied.
        ],
    ]
```


To make this Filter work as a CountableFilter, change the `ProductFilter` class so that it extends `CountableFilter` instead:

```php
use Czim\Filter\CountableFilter;

class ProductFilter extends CountableFilter
{
```

And add the following to it:

```php

    // Only return counts for the brands and categories related
    protected $countables = [
        'brands',
        'categories',
    ];


    /**
     * @param string $parameter name of the countable parameter
     * @return EloquentBuilder
     */
    protected function getCountableBaseQuery($parameter = null)
    {
        return \App\Product::query();
    }

    protected function countStrategies()
    {
        return [
        
            // For the given example call, this would return all
            // Brand id's with product counts for each; but only for
            // the subset that results from filtering by categories
            // (or any other filter parameter other than brands itself).
            
            'brands' => new ParameterCounters\SimpleBelongsTo(),
        ];
        
        // 'categories' is not present here either, since it 
        // will similarly be handled in the countParameter method.
    }


    /**
     * @param string          $parameter countable name
     * @param EloquentBuilder $query
     * @return mixed
     */
    protected function countParameter($parameter, $query)
    {
        switch ($parameter) {

            case 'categories':
                
                // The query that will be executed for this is modified to include
                // all parameters (in the example, none will be applied for categories,
                // so it would be the same as executing it on Product:: instead of the
                // $query parameter here.
                
                return $query->select('category_product.category_id AS id', \DB::raw('COUNT(*) AS count'))
                             ->groupBy('category_product.category_id')
                             ->join('category_product', 'category_product.product_id', '=', 'products.id')
                             ->pluck('count', 'id');

        }
        
        return parent::countParameter($parameter, $query);
    }
```
