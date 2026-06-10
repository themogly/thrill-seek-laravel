<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\ShopItems\Pages\CreateShopItem;
use App\Models\ShopItem;
use App\Models\User;
use Database\Seeders\ShopItemSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class ShopTest extends TestCase
{
    public function test_shop_page_lists_items_in_order(): void
    {
        ShopItem::factory()->create(['name' => 'Zeta Cap', 'sort_order' => 2]);
        ShopItem::factory()->create(['name' => 'Alpha Hoodie', 'sort_order' => 1]);

        $response = $this->get('/shop');

        $response->assertOk();
        $response->assertSeeInOrder(['Alpha Hoodie', 'Zeta Cap']);
    }

    public function test_seeded_content_matches_the_original_static_page(): void
    {
        $this->seed(ShopItemSeeder::class);

        $this->get('/shop')
            ->assertSee('T-Shirt')
            ->assertSee('£15 – £30')
            ->assertSee('Made-to-measure G-Force jumpsuit.')
            ->assertSee('Insulated G-Force water bottle.');
    }

    public function test_admin_can_create_a_shop_item(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateShopItem::class)
            ->fillForm([
                'name' => 'Beanie',
                'price_label' => '£12',
                'description' => 'Warm G-Force beanie.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('shop_items', ['name' => 'Beanie']);
    }
}
