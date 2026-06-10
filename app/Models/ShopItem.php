<?php

namespace App\Models;

use App\Observers\SiteContentObserver;
use Database\Factories\ShopItemFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(SiteContentObserver::class)]
class ShopItem extends Model
{
    /** @use HasFactory<ShopItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'price_label',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
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
