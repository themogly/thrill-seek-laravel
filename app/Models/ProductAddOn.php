<?php

namespace App\Models;

use App\Observers\SiteContentObserver;
use App\Support\Money;
use Database\Factories\ProductAddOnFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A priced line attached to a product: optional extras the customer can buy
 * (camera packages) and fixed fees shown for transparency (insurance,
 * rebooking). Only purchasable add-ons can be included in payments.
 */
#[ObservedBy(SiteContentObserver::class)]
class ProductAddOn extends Model
{
    /** @use HasFactory<ProductAddOnFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'price_pence',
        'note',
        'purchasable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_pence' => 'integer',
            'purchasable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(get: fn (): string => Money::formatPence($this->price_pence));
    }
}
