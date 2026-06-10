<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingUnavailableException;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\StripeCheckout;
use Illuminate\Support\Facades\DB;

class StartAffCheckout
{
    public function __construct(private readonly StripeCheckout $stripe) {}

    /**
     * Hold a place on the course and send the customer to Stripe for the
     * deposit; the balance is settled later through the existing admin flows.
     *
     * @param  array{
     *     name: string, email: string, phone: string, date_of_birth: string,
     *     weight_kg: string|int, emergency_contact_name: string,
     *     emergency_contact_phone: string, experience?: string|null,
     * }  $customer
     * @return array{booking: Booking, checkout_url: string}
     */
    public function handle(CourseDate $courseDate, array $customer): array
    {
        $booking = DB::transaction(function () use ($courseDate, $customer): Booking {
            $lockedCourse = CourseDate::lockForUpdate()->findOrFail($courseDate->id);

            if (! $lockedCourse->isBookable()) {
                throw new BookingUnavailableException('That course has just filled up — please pick another date.');
            }

            $customerRecord = Customer::resolve($customer['email'], $customer['name'], $customer['phone']);

            return Booking::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'product_id' => $lockedCourse->product_id,
                'customer_id' => $customerRecord->id,
                'status' => BookingStatus::PendingPayment,
                'course_date_id' => $lockedCourse->id,
                'scheduled_at' => $lockedCourse->start_date->copy()->setTime(8, 0),
                'price_pence' => (int) $lockedCourse->effective_price_pence,
                'customer_details' => [
                    'date_of_birth' => $customer['date_of_birth'],
                    'weight_kg' => (string) $customer['weight_kg'],
                    'emergency_contact_name' => $customer['emergency_contact_name'],
                    'emergency_contact_phone' => $customer['emergency_contact_phone'],
                    'experience' => $customer['experience'] ?? '',
                    'course' => $lockedCourse->date_range_label.' — '.$lockedCourse->location->name,
                ],
            ]);
        });

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'purpose' => PaymentPurpose::AffDeposit,
            'method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Pending,
            'amount_pence' => (int) $courseDate->effective_deposit_pence,
            'description' => 'AFF course deposit — '.$courseDate->location->name.' ('.$courseDate->date_range_label.')',
        ]);

        try {
            $session = $this->stripe->createSession(
                $payment,
                expiresAfterMinutes: StartTandemCheckout::HOLD_MINUTES,
                cancelUrl: route('payment.cancelled', ['flow' => 'aff']),
            );
        } catch (\Throwable $e) {
            $booking->update(['status' => BookingStatus::Cancelled]);
            $payment->update(['status' => PaymentStatus::Failed]);

            throw $e;
        }

        $payment->update(['stripe_checkout_session_id' => $session['id']]);

        return ['booking' => $booking, 'checkout_url' => $session['url']];
    }
}
