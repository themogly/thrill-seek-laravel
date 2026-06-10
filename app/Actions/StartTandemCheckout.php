<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingUnavailableException;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\StripeCheckout;
use Illuminate\Support\Facades\DB;

class StartTandemCheckout
{
    /** How long a held place survives without payment. */
    public const HOLD_MINUTES = 30;

    public function __construct(
        private readonly StripeCheckout $stripe,
        private readonly RedeemVoucher $redeemVoucher,
        private readonly ConfirmHeldBooking $confirmHeldBooking,
    ) {}

    /**
     * Hold a place on the slot (inside a lock so concurrent customers cannot
     * overbook), then send the customer to Stripe for whatever a voucher
     * doesn't cover. A voucher covering the whole amount books immediately
     * with no Stripe round-trip; a partial voucher is only consumed when
     * the card payment succeeds (webhook), so an abandoned checkout never
     * burns it.
     *
     * @param  array{
     *     name: string, email: string, phone: string, date_of_birth: string,
     *     weight_kg: string|int, emergency_contact_name: string,
     *     emergency_contact_phone: string, medical_notes?: string|null,
     * }  $customer
     * @param  list<int>  $addOnIds  selected purchasable add-on ids
     * @return array{booking: Booking, checkout_url: string|null}
     */
    public function handle(AvailabilitySlot $slot, Product $product, array $customer, array $addOnIds = [], ?Voucher $voucher = null): array
    {
        if ($voucher !== null && ! $voucher->isRedeemable()) {
            throw new BookingUnavailableException('That voucher code is no longer valid — remove it or contact us.');
        }
        $addOns = $product->addOns()
            ->where('purchasable', true)
            ->whereIn('id', $addOnIds)
            ->get();

        $totalPence = (int) $product->price_pence + (int) $addOns->sum('price_pence');

        $booking = DB::transaction(function () use ($slot, $product, $customer, $addOns, $totalPence): Booking {
            $lockedSlot = AvailabilitySlot::lockForUpdate()->findOrFail($slot->id);

            if ($lockedSlot->isFull() || $lockedSlot->starts_at->isPast()) {
                throw new BookingUnavailableException('That date has just filled up — please pick another.');
            }

            $customerRecord = Customer::resolve($customer['email'], $customer['name'], $customer['phone']);

            return Booking::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'product_id' => $product->id,
                'customer_id' => $customerRecord->id,
                'status' => BookingStatus::PendingPayment,
                'availability_slot_id' => $lockedSlot->id,
                'scheduled_at' => $lockedSlot->starts_at,
                'price_pence' => $totalPence,
                'customer_details' => [
                    'date_of_birth' => $customer['date_of_birth'],
                    'weight_kg' => (string) $customer['weight_kg'],
                    'emergency_contact_name' => $customer['emergency_contact_name'],
                    'emergency_contact_phone' => $customer['emergency_contact_phone'],
                    'medical_notes' => $customer['medical_notes'] ?? '',
                    'add_ons' => $addOns->pluck('name')->implode(', ') ?: 'None',
                ],
            ]);
        });

        $voucherCoverage = $voucher === null ? 0 : min($voucher->amount_pence, $totalPence);
        $duePence = $totalPence - $voucherCoverage;

        // Fully covered: redeem and confirm on the spot — nothing to charge.
        if ($voucher !== null && $duePence === 0) {
            $this->redeemVoucher->handle($voucher, $booking);
            $this->confirmHeldBooking->handle($booking->payments()->latest('id')->firstOrFail());

            return ['booking' => $booking->refresh(), 'checkout_url' => null];
        }

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'purpose' => PaymentPurpose::TandemFull,
            'method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Pending,
            'amount_pence' => $duePence,
            'description' => $product->name.($addOns->isNotEmpty() ? ' + '.$addOns->pluck('name')->implode(', ') : ''),
            'metadata' => $voucher === null ? null : ['voucher_id' => $voucher->id],
        ]);

        try {
            $session = $this->stripe->createSession(
                $payment,
                expiresAfterMinutes: self::HOLD_MINUTES,
                cancelUrl: route('payment.cancelled', ['flow' => 'tandem']),
            );
        } catch (\Throwable $e) {
            // Release the held place immediately; the customer sees a
            // friendly message from the booking flow.
            $booking->update(['status' => BookingStatus::Cancelled]);
            $payment->update(['status' => PaymentStatus::Failed]);

            throw $e;
        }

        $payment->update(['stripe_checkout_session_id' => $session['id']]);

        return ['booking' => $booking, 'checkout_url' => $session['url']];
    }
}
