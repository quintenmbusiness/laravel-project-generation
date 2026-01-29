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

## Example Generated Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Order extends Model
{
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

## Notes

- Commit or back up your code before running the generator.
- All schema inspection is handled by LaravelAnalyzer.
