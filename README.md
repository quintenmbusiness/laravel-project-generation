# laravel-project-generation

Generate Laravel Eloquent models from your database schema using LaravelAnalyzer.

This package currently generates **only Eloquent models**.

---

## Dependencies

This package relies on **LaravelAnalyzer** to inspect migrations and database structure.

- LaravelAnalyzer (Packagist): https://packagist.org/packages/quintenmbusiness/laravel-analyzer
- LaravelAnalyzer Wiki / Docs: https://github.com/quintenmbusiness/LaravelAnalyzer/wiki

All schema information (tables, columns, relations) is provided by LaravelAnalyzer.

---

## Installation

Install the package via Composer:

    composer require quintenmbusiness/laravel-project-generation

This package requires PHP 8.1+ and will automatically install:
- quintenmbusiness/laravel-analyzer (v1.3)
- laravel/prompts

---

## Service Provider

If Laravel package auto-discovery is disabled, manually register the service provider.

Add the following to `config/app.php`:

    'providers' => [
        quintenmbusiness\LaravelProjectGeneration\LaravelProjectGenerationServiceProvider::class,
    ],

---

## Usage

Run the interactive generator command:

    php artisan project:generate

---

## Interactive Flow

- Select a database connection  
  Only `sqlite` and `mysql` connections are shown.
- Select preview detail level
    - A: Tables, columns, relationships
    - B: Tables and columns
    - C: Tables only
    - D: Nothing
- Select class types to generate
    - Currently supported: Model
- Optional file tree preview
- Multiple safety confirmations before overwriting files

---

## Output

- Models are generated in `app/Models`
- Existing files are overwritten after confirmation
- Relationships are inferred using LaravelAnalyzer

---

This will then generate for you:

## Example Generated Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Order extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = 'orders';
    protected $fillable = [
        'order_number',
        'customer_id',
        'billing_address_id',
        'shipping_address_id',
        'total_amount',
        'discount_amount',
        'status',
        'metadata',
    ];
    protected $casts = [
        'id' => 'int',
        'customer_id' => 'int',
        'billing_address_id' => 'int',
        'shipping_address_id' => 'int',
        'total_amount' => 'float',
        'discount_amount' => 'float',
    ];

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(BillingAddress::class, 'billing_address_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id', 'id');
    }

    public function orderCoupons(): HasMany
    {
        return $this->hasMany(OrderCoupon::class, 'order_id', 'id');
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'id');
    }

    public function coupons(): HasManyThrough
    {
        return $this->hasManyThrough(Coupon::class, OrderCoupon::class, 'order_id', 'coupon_id', 'id', 'id');
    }

    public function productVariants(): HasManyThrough
    {
        return $this->hasManyThrough(ProductVariant::class, OrderProduct::class, 'order_id', 'product_variant_id', 'id', 'id');
    }
}
```

---

## Example Generated Factory

```php
<?php

namespace Database\Factories;

use App\Models\BillingAddress;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ShippingAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $billingAddress = BillingAddress::factory();
        $customer = Customer::factory();
        $shippingAddress = ShippingAddress::factory();

        return [
            'billing_address_id' => $billingAddress,
            'customer_id' => $customer,
            'shipping_address_id' => $shippingAddress,
            'order_number' => $this->faker->word(),
            'total_amount' => $this->faker->randomFloat(2, 0, 1000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 1000),
            'status' => $this->faker->word(),
            'metadata' => $this->faker->text(500),
        ];
    }
}

```

---

## Example Migration file used

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcommerceTables extends Migration
{
    public function up()
    {
        Schema::create('categories', static function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('products', static function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('price', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variants', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->json('attributes')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_addresses', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_addresses', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', static function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('type');
            $table->decimal('value', 12, 2);
            $table->dateTime('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', static function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('billing_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('shipping_addresses')->nullOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('order_products', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('order_coupons', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->decimal('discount_amount', 12, 2);
            $table->timestamps();
        });
    }
}
```

---

## Notes

- Commit or back up your code before running the generator.
- All schema inspection is handled by LaravelAnalyzer.
