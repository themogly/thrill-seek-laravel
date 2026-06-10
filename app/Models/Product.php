<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductType;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property ProductType $type
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'summary',
        'description',
        'image',
        'page_path',
        'price_pence',
        'deposit_pence',
        'price_note',
        'show_from_price',
        'duration',
        'features',
        'weight_charges',
        'repeat_pricing',
        'highlight',
        'featured_on_home',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price_pence' => 'integer',
            'deposit_pence' => 'integer',
            'show_from_price' => 'boolean',
            'features' => 'array',
            'weight_charges' => 'array',
            'repeat_pricing' => 'array',
            'highlight' => 'boolean',
            'featured_on_home' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<ProductAddOn, $this> */
    public function addOns(): HasMany
    {
        return $this->hasMany(ProductAddOn::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Exact price, e.g. "£260" or "£1,750".
     *
     * @return Attribute<string|null, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->price_pence === null
            ? null
            : Money::formatPence($this->price_pence));
    }

    /**
     * Deposit, e.g. "£200".
     *
     * @return Attribute<string|null, never>
     */
    protected function formattedDeposit(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->deposit_pence === null
            ? null
            : Money::formatPence($this->deposit_pence));
    }

    /**
     * The short price label used on summary cards: "from £260", "£1,750"
     * or "Price on enquiry".
     *
     * @return Attribute<string, never>
     */
    protected function summaryPriceLabel(): Attribute
    {
        return Attribute::make(get: function (): string {
            if ($this->price_pence === null || (! $this->show_from_price && $this->type === ProductType::Coaching)) {
                return 'Price on enquiry';
            }

            return $this->show_from_price
                ? 'from '.Money::formatPence($this->price_pence)
                : Money::formatPence($this->price_pence);
        });
    }

    /**
     * Public URL for the card image (bundled path or admin upload).
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if ($this->image === null) {
                return null;
            }

            return str_starts_with($this->image, '/')
                ? $this->image
                : Storage::disk('public')->url($this->image);
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, ProductType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Display order used by the public site.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
