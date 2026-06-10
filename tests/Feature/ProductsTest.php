<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    public function test_home_services_cards_come_from_products(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Tandem Skydive', 'AFF Course Levels 1–8', 'Coached Skills'])
            ->assertSee('from £260')
            ->assertSee('£1,750')
            ->assertSee('from £60')
            ->assertDontSee('Consolidation Jumps');
    }

    public function test_tandem_page_pricing_tables_come_from_the_product(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/tandem')
            ->assertOk()
            ->assertSee('Paid direct to G-Force')
            ->assertSeeInOrder(['Outside Camera', '£140', 'HandCam', '£100', 'P6 Third Party Insurance', '£24.73', 'Rebooking Fee', '£50'])
            ->assertSeeInOrder(['Up to 15st', 'Free', '18st+', 'Assessment required'])
            ->assertSee('Choose a date &amp; book', false)
            ->assertSee('/book/tandem');
    }

    public function test_aff_page_price_cards_come_from_products(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/aff')
            ->assertOk()
            ->assertSeeInOrder(['AFF Course Levels 1–8', '£1,750', 'Consolidation Jumps', '£600'])
            ->assertSee('Repeat jump pricing')
            ->assertSeeInOrder(['Levels 1–3', '£210 per jump', 'Levels 4–7', '£140 per jump']);
    }

    public function test_price_changes_invalidate_the_cached_pages(): void
    {
        $this->seed(ProductSeeder::class);
        $this->get('/tandem')->assertSee('£260');

        $product = Product::where('slug', 'tandem-skydive')->firstOrFail();
        $product->update(['price_pence' => 28000]);

        $this->get('/tandem')->assertSee('£280');
    }

    public function test_admin_can_create_a_product(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Night Jump Special',
                'slug' => 'night-jump-special',
                'type' => 'tandem',
                'price_pence' => 30000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', ['slug' => 'night-jump-special', 'price_pence' => 30000]);
    }
}
