# Migrations

```php
// ..._create_products_table.php
class CreateProductsTable extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('ean', 20)->nullable();
            $table->text('description');
            $table->integer('brand_id')->unsigned();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('products');
    }
}


// ..._create_brands_table.php
class CreateBrandsTable extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->timestamps();
        });

        Schema::table('products', function(Blueprint $table) {

            $table->foreign('brand_id', 'fk_products_brands1')
                ->references('id')
                ->on('brands')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('products', function(Blueprint $table) {

            $table->dropForeign('fk_products_brands1');
        });

        Schema::drop('brands');
    }
}


// ..._create_categories_table.php
class CreateCategoriesTable extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->timestamps();
        });

        Schema::create('category_product', function(Blueprint $table) {
            $table->integer('category_id')->unsigned();
            $table->integer('product_id')->unsigned();

            $table->foreign('category_id', 'fk_category_product_categories1')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');

            $table->foreign('product_id', 'fk_category_product_products1')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->primary(['category_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::drop('category_product');
        Schema::drop('categories');
    }
}
```

# Models

## Product

```php
<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'ean',
        'description',
    ];


    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
```


## Brand

```php
<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'name',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

## Category

```php
<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
```
