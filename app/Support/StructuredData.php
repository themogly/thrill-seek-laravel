<?php

namespace App\Support;

use App\Models\CourseDate;
use App\Models\Faq;
use App\Models\Location;
use App\Models\NewsArticle;
use App\Models\Product;
use App\Models\Testimonial;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds valid schema.org JSON-LD from real model/CMS data only — never
 * invented values. Rendered via <x-seo.json-ld :data="..." />. All money is
 * the canonical integer pence converted to GBP units.
 */
class StructuredData
{
    /**
     * Sitewide business identity. A dropzone is a SportsActivityLocation (a
     * LocalBusiness). A full postal address is an owner task — emitted only
     * once the CMS holds one; areaServed carries the location meanwhile.
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $g = app(GeneralSettings::class);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SportsActivityLocation',
            'name' => $g->site_name,
            'url' => url('/'),
            'logo' => url('/images/logo.png'),
            'image' => url('/images/hero-skydive.jpg'),
            'email' => $g->email,
            'telephone' => $g->phone,
            'areaServed' => ['Devon', 'United Kingdom', 'Seville, Spain'],
            'sameAs' => array_values(array_filter([$g->instagram_url, $g->facebook_url])),
        ]);
    }

    /**
     * Product + Offer for a sellable jump/course, with the real current price.
     *
     * @return array<string, mixed>
     */
    public static function product(Product $product, string $url, string $description): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'brand' => ['@type' => 'Brand', 'name' => app(GeneralSettings::class)->site_name],
            'url' => $url,
        ];

        if ($product->price_pence !== null) {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => number_format($product->price_pence / 100, 2, '.', ''),
                'priceCurrency' => 'GBP',
                'availability' => 'https://schema.org/InStock',
                'url' => $url,
            ];
        }

        return $data;
    }

    /**
     * Fewer real reviews than this and no rating is published, so one or two
     * reviews can't put a star figure in search results.
     * See DECISIONS (OVERNIGHT-DEFAULT — CONFIRM).
     */
    public const MIN_REVIEWS_FOR_RATING = 3;

    /**
     * AggregateRating from REAL customers' reviews only: approved testimonials a
     * customer submitted from their account (customer_id set) with a star rating.
     * Seeded samples and reviews the owner typed in have no customer and never
     * count. The page renders the same collection, so the figures agree.
     *
     * @param  Collection<int, Testimonial>  $testimonials
     * @return array<string, mixed>|null
     */
    public static function aggregateRating(Collection $testimonials): ?array
    {
        $rated = $testimonials
            ->filter(fn (Testimonial $t): bool => $t->customer_id !== null && $t->rating !== null)
            ->values();

        if ($rated->count() < self::MIN_REVIEWS_FOR_RATING) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => app(GeneralSettings::class)->site_name,
            'url' => url('/'),
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => round($rated->avg('rating'), 1),
                'reviewCount' => $rated->count(),
                'bestRating' => 5,
                'worstRating' => 1,
            ],
        ];
    }

    /**
     * Article for a news post.
     *
     * @return array<string, mixed>
     */
    public static function article(NewsArticle $article, string $url, ?string $imageUrl): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'datePublished' => $article->published_at?->toAtomString(),
            'dateModified' => $article->updated_at?->toAtomString(),
            'author' => filled($article->byline)
                ? ['@type' => 'Organization', 'name' => $article->byline]
                : ['@type' => 'Organization', 'name' => app(GeneralSettings::class)->site_name],
            'publisher' => [
                '@type' => 'Organization',
                'name' => app(GeneralSettings::class)->site_name,
                'logo' => ['@type' => 'ImageObject', 'url' => url('/images/logo.png')],
            ],
            'image' => $imageUrl,
            'mainEntityOfPage' => $url,
        ]);
    }

    /**
     * Event for an AFF course date (real dates, location and deposit).
     *
     * @return array<string, mixed>
     */
    public static function courseEvent(CourseDate $course, string $url): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => ($course->product->name ?? 'AFF Course').' — '.$course->location->name,
            'startDate' => $course->start_date->toDateString(),
            'endDate' => $course->end_date->toDateString(),
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => self::place($course->location),
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format((int) $course->effective_deposit_pence / 100, 2, '.', ''),
                'priceCurrency' => 'GBP',
                'availability' => $course->isBookable() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'url' => $url,
            ],
        ]);
    }

    /**
     * BreadcrumbList from an ordered [label => url] map.
     *
     * @param  array<string, string>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            // Position comes from the (1-based) index — keys are labels, values URLs.
            'itemListElement' => collect($items)
                ->map(fn (string $url, string $name): array => ['name' => $name, 'url' => $url])
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }

    /**
     * FAQPage from a page's FAQs. The answer is the PLAIN-TEXT version so the schema
     * matches the visible (rich) answer exactly — Google requires that parity.
     *
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>
     */
    public static function faqPage(Collection $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq): array => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->plainAnswer(),
                ],
            ])->values()->all(),
        ];
    }

    /**
     * A schema.org Place for a dropzone, from the Location's REAL address fields only:
     * a PostalAddress built from whichever are filled (omitted entirely when none are —
     * never the name repeated as an address), and geo only when both coordinates exist.
     *
     * @return array<string, mixed>
     */
    public static function place(Location $location): array
    {
        $address = array_filter([
            'streetAddress' => $location->address_line,
            'addressLocality' => $location->town,
            'addressRegion' => $location->region,
            'postalCode' => $location->postcode,
            'addressCountry' => $location->country,
        ], fn (?string $value): bool => filled($value));

        return array_filter([
            '@type' => 'Place',
            'name' => $location->name,
            'address' => $address === [] ? null : ['@type' => 'PostalAddress', ...$address],
            'geo' => $location->lat !== null && $location->lng !== null
                ? ['@type' => 'GeoCoordinates', 'latitude' => $location->lat, 'longitude' => $location->lng]
                : null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
