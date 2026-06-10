<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ProductType;
use App\Models\GalleryImage;
use App\Models\HallOfFameEntry;
use App\Models\Instructor;
use App\Models\Product;
use App\Models\ProductAddOn;
use App\Models\ShopItem;
use App\Models\Testimonial;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Cached reads for the public site's hot content.
 *
 * Laravel 13's cache refuses to unserialize PHP objects
 * (cache.serializable_classes = false), so ONLY raw attribute arrays are
 * cached; models are rehydrated on read. SiteContentObserver busts the
 * relevant keys whenever a content model is saved or deleted.
 */
final class SiteContent
{
    private const PREFIX = 'site-content.';

    /** @var array<class-string<Model>, list<string>> */
    public const KEYS_BY_MODEL = [
        Instructor::class => ['instructors'],
        Testimonial::class => ['testimonials.featured', 'testimonials.all'],
        GalleryImage::class => ['gallery'],
        Product::class => ['products.home', 'products.tandem', 'products.aff'],
        ProductAddOn::class => ['products.tandem'],
        ShopItem::class => ['shop'],
        HallOfFameEntry::class => ['hall_of_fame'],
    ];

    /** @return EloquentCollection<int, Instructor> */
    public function instructors(): EloquentCollection
    {
        return Instructor::hydrate($this->rows('instructors', fn () => Instructor::ordered()->get()));
    }

    /** @return EloquentCollection<int, Testimonial> */
    public function featuredTestimonials(): EloquentCollection
    {
        return Testimonial::hydrate($this->rows(
            'testimonials.featured',
            fn () => Testimonial::featured()->ordered()->limit(3)->get(),
        ));
    }

    /** @return EloquentCollection<int, Testimonial> */
    public function allTestimonials(): EloquentCollection
    {
        return Testimonial::hydrate($this->rows('testimonials.all', fn () => Testimonial::ordered()->get()));
    }

    /** @return EloquentCollection<int, GalleryImage> */
    public function galleryImages(): EloquentCollection
    {
        return GalleryImage::hydrate($this->rows('gallery', fn () => GalleryImage::ordered()->get()));
    }

    /** @return EloquentCollection<int, Product> */
    public function homeServices(): EloquentCollection
    {
        return Product::hydrate($this->rows(
            'products.home',
            fn () => Product::active()->where('featured_on_home', true)->ordered()->get(),
        ));
    }

    /** The tandem product with its add-ons relation, or null when none is active. */
    public function tandemProduct(): ?Product
    {
        /** @var array{product: array<string, mixed>|null, add_ons: list<array<string, mixed>>} $data */
        $data = Cache::rememberForever(self::PREFIX.'products.tandem', function (): array {
            $product = Product::active()->ofType(ProductType::Tandem)->ordered()->with('addOns')->first();

            return [
                'product' => $product?->getAttributes(),
                'add_ons' => $product?->addOns->map(fn (ProductAddOn $addOn): array => $addOn->getAttributes())->all() ?? [],
            ];
        });

        if ($data['product'] === null) {
            return null;
        }

        $product = Product::hydrate([$data['product']])->first();
        $product->setRelation('addOns', ProductAddOn::hydrate($data['add_ons']));

        return $product;
    }

    /** @return EloquentCollection<int, Product> */
    public function affProducts(): EloquentCollection
    {
        return Product::hydrate($this->rows(
            'products.aff',
            fn () => Product::active()->ofType(ProductType::Aff)->ordered()->get(),
        ));
    }

    /** @return EloquentCollection<int, ShopItem> */
    public function shopItems(): EloquentCollection
    {
        return ShopItem::hydrate($this->rows('shop', fn () => ShopItem::ordered()->get()));
    }

    /** @return EloquentCollection<int, HallOfFameEntry> */
    public function hallOfFame(): EloquentCollection
    {
        return HallOfFameEntry::hydrate($this->rows('hall_of_fame', fn () => HallOfFameEntry::ordered()->get()));
    }

    /** @param  class-string<Model>  $modelClass */
    public static function flushFor(string $modelClass): void
    {
        foreach (self::KEYS_BY_MODEL[$modelClass] ?? [] as $key) {
            Cache::forget(self::PREFIX.$key);
        }
    }

    /**
     * Cache the raw attribute arrays for a query of models.
     *
     * @template TModel of Model
     *
     * @param  Closure(): EloquentCollection<int, TModel>  $query
     * @return list<array<string, mixed>>
     */
    private function rows(string $key, Closure $query): array
    {
        return Cache::rememberForever(
            self::PREFIX.$key,
            fn (): array => $query()->map(fn (Model $model): array => $model->getAttributes())->all(),
        );
    }
}
