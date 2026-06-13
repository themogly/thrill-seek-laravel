<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Livewire\BookTandem;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TandemDate;
use App\Models\User;
use App\Services\StripeCheckout;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Highest-risk item: money is entered in pounds in the admin but stored and
 * charged in pence. A wrong conversion would charge customers 100× or 1/100,
 * so this asserts the conversion end to end through Stripe.
 */
class PricingInPoundsTest extends TestCase
{
    public function test_pounds_entry_is_stored_as_pence(): void
    {
        $this->seed(ProductSeeder::class);
        $this->actingAs(User::factory()->create());
        $tandem = Product::where('slug', 'tandem-skydive')->firstOrFail();

        // The form shows the existing price in pounds (260), not pence (26000)…
        $component = Livewire::test(EditProduct::class, ['record' => $tandem->id]);
        $this->assertEquals(260, $component->get('data.price_pence'));

        // …and a pounds entry is persisted as pence.
        $component->fillForm(['price_pence' => 300])->call('save')->assertHasNoFormErrors();
        $this->assertSame(30000, $tandem->refresh()->price_pence);
    }

    public function test_pounds_entry_flows_through_to_the_stripe_charge_amount(): void
    {
        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();

        $charged = null;
        $this->mock(StripeCheckout::class, function ($mock) use (&$charged): void {
            $mock->shouldReceive('createSession')->andReturnUsing(function (Payment $payment) use (&$charged): array {
                $charged = $payment->amount_pence;

                return ['id' => 'cs_test_pounds', 'url' => 'https://checkout.stripe.test/cs_test_pounds'];
            });
        });

        // Owner sets the tandem price to £312.50 in the admin (pounds).
        $this->actingAs(User::factory()->create());
        $tandem = Product::where('slug', 'tandem-skydive')->firstOrFail();
        Livewire::test(EditProduct::class, ['record' => $tandem->id])
            ->fillForm(['price_pence' => 312.50])
            ->call('save')
            ->assertHasNoFormErrors();

        // A customer books and pays — Stripe must be charged 31250 pence, not 312 or 3125000.
        $slot = TandemDate::factory()->create(['starts_at' => now()->addWeeks(2)->setTime(9, 0), 'capacity' => 4]);

        Livewire::test(BookTandem::class)
            ->call('chooseSlot', $slot->id)
            ->set('name', 'Jess Jumper')
            ->set('email', 'jumper@example.com')
            ->set('phone', '07700900123')
            ->set('date_of_birth', now()->subYears(28)->format('Y-m-d'))
            ->set('weight_kg', '80')
            ->set('emergency_contact_name', 'Pat Carer')
            ->set('emergency_contact_phone', '07700900456')
            ->call('continueToReview')
            ->set('terms', true)
            ->call('pay');

        $this->assertSame(31250, $charged);
        $this->assertSame(31250, Booking::sole()->price_pence);
        $this->assertSame(31250, Payment::sole()->amount_pence);
    }
}
