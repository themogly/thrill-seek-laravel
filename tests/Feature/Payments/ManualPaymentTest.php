<?php

namespace Tests\Feature\Payments;

use App\Enums\EnquiryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();
        $this->actingAs(User::factory()->create());
    }

    public function test_recording_a_bank_transfer_converts_the_enquiry(): void
    {
        $product = Product::where('slug', 'aff-course')->firstOrFail();
        $enquiry = Enquiry::factory()->create(['product_id' => $product->id]);

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->callAction('recordBankTransfer', [
                'purpose' => PaymentPurpose::AffDeposit->value,
                'amount_pence' => 300, // pounds
                'reference' => 'FPS-998877',
                'paid_at' => now()->toDateString(),
            ])
            ->assertHasNoActionErrors();

        $payment = Payment::sole();
        $this->assertSame(PaymentMethod::BankTransfer, $payment->method);
        $this->assertTrue($payment->isPaid());
        $this->assertSame('FPS-998877', $payment->reference);

        $booking = Booking::sole();
        $this->assertSame(145000, $booking->balance_due_pence);
        $this->assertSame(EnquiryStatus::Converted, $enquiry->refresh()->status);
    }

    public function test_a_second_payment_attaches_to_the_existing_booking(): void
    {
        $product = Product::where('slug', 'aff-course')->firstOrFail();
        $enquiry = Enquiry::factory()->create(['product_id' => $product->id]);

        $page = Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()]);

        $page->callAction('recordBankTransfer', [
            'purpose' => PaymentPurpose::AffDeposit->value,
            'amount_pence' => 300, // pounds
            'reference' => 'DEPOSIT-1',
            'paid_at' => now()->toDateString(),
        ]);

        $page->callAction('recordBankTransfer', [
            'purpose' => PaymentPurpose::AffBalance->value,
            'amount_pence' => 1450, // pounds
            'reference' => 'BALANCE-1',
            'paid_at' => now()->toDateString(),
        ]);

        $booking = Booking::sole();
        $this->assertSame(2, $booking->payments()->count());
        $this->assertSame(0, $booking->refresh()->balance_due_pence);
        $this->assertFalse($booking->hasOutstandingBalance());
    }
}
