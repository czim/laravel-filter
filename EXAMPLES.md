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

add example for countable filter
