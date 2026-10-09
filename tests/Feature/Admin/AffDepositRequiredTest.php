<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * An AFF product with no deposit drops out of the course-date product picker and
 * its courses stop being bookable — so the deposit is required for AFF.
 */
class AffDepositRequiredTest extends TestCase
{
    public function test_an_aff_product_needs_a_deposit(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->aff()->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['deposit_pence' => null])
            ->call('save')
            ->assertHasFormErrors(['deposit_pence' => 'required']);
    }

    public function test_a_tandem_product_does_not(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->tandem()->create(['deposit_pence' => null]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
