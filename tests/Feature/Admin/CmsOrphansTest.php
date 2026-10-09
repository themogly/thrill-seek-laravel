<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Settings\ManageHomePageSettings;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\CourseDate;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Settings\HomePageSettings;
use App\Support\StructuredData;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

/**
 * Every admin field is consumed somewhere (012): the Location address finally
 * feeds the course Event's structured data, and the three fields that fed
 * nothing — Product duration, the homepage team lead, the Location photo — are gone.
 */
class CmsOrphansTest extends TestCase
{
    public function test_a_course_event_carries_the_locations_real_postal_address_and_geo(): void
    {
        $location = Location::factory()->create([
            'name' => 'Swansea Dropzone', 'address_line' => 'Swansea Airport', 'town' => 'Fairwood',
            'region' => 'Swansea', 'postcode' => 'SA2 7JU', 'country' => 'United Kingdom', 'lat' => 51.6053, 'lng' => -4.0678,
        ]);
        $event = StructuredData::courseEvent(CourseDate::factory()->create(['location_id' => $location->id]), 'https://example.test/aff');

        $this->assertSame('Swansea Dropzone', $event['location']['name']);
        $this->assertSame([
            '@type' => 'PostalAddress',
            'streetAddress' => 'Swansea Airport',
            'addressLocality' => 'Fairwood',
            'addressRegion' => 'Swansea',
            'postalCode' => 'SA2 7JU',
            'addressCountry' => 'United Kingdom',
        ], $event['location']['address']);
        $this->assertSame(['@type' => 'GeoCoordinates', 'latitude' => 51.6053, 'longitude' => -4.0678], $event['location']['geo']);
    }

    public function test_partial_address_is_emitted_and_the_name_is_never_passed_off_as_an_address(): void
    {
        $partial = Location::factory()->create(['name' => 'Devon', 'address_line' => null, 'town' => null, 'region' => 'Devon', 'postcode' => null, 'country' => 'United Kingdom', 'lat' => 50.7, 'lng' => null]);
        $event = StructuredData::courseEvent(CourseDate::factory()->create(['location_id' => $partial->id]), 'https://example.test/aff');
        $this->assertSame(['@type' => 'PostalAddress', 'addressRegion' => 'Devon', 'addressCountry' => 'United Kingdom'], $event['location']['address']);
        $this->assertArrayNotHasKey('geo', $event['location'], 'geo needs both lat and lng.');

        $bare = Location::factory()->create(['name' => 'Nowhere Yet', 'address_line' => null, 'town' => null, 'region' => null, 'postcode' => null, 'country' => '', 'lat' => null, 'lng' => null]);
        $event = StructuredData::courseEvent(CourseDate::factory()->create(['location_id' => $bare->id]), 'https://example.test/aff');
        $this->assertArrayNotHasKey('address', $event['location']);
        $this->assertSame('Nowhere Yet', $event['location']['name']);
    }

    public function test_the_removed_fields_are_gone_from_the_admin_and_the_schema(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(EditProduct::class, ['record' => Product::factory()->create()->getRouteKey()])->assertFormFieldDoesNotExist('duration');
        Livewire::test(EditLocation::class, ['record' => Location::factory()->create()->getRouteKey()])->assertFormFieldDoesNotExist('image');
        Livewire::test(ManageHomePageSettings::class)->assertFormFieldDoesNotExist('team_lead');

        $this->assertFalse(Schema::hasColumn('products', 'duration'));
        $this->assertFalse(Schema::hasColumn('locations', 'image'));
    }

    public function test_home_settings_load_without_team_lead_even_from_a_stale_cached_payload(): void
    {
        $this->assertNotEmpty(app(HomePageSettings::class)->hero_title_1);

        // A cache written before the deploy still carries the removed key.
        $stale = (new ReflectionClass(HomePageSettings::class))->newInstanceWithoutConstructor();
        $stale->__unserialize([...app(HomePageSettings::class)->toArray(), 'team_lead' => "The people you'll fly with."]);

        $this->assertNotEmpty($stale->hero_title_1);
        $this->get('/')->assertOk();
    }
}
