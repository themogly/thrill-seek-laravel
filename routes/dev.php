<?php

use App\Enums\MessageDirection;
use App\Mail\CourseMessageMail;
use App\Mail\EnquiryAdminNotification;
use App\Mail\EnquiryReplyMail;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Mail\VoucherGiftMail;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use App\Models\EmailTemplate;
use App\Models\Enquiry;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Local-only email previews
|--------------------------------------------------------------------------
| Renders every mailable the system can send with sample data, inside a
| rolled-back transaction so nothing persists. Loaded only when
| app()->environment('local') — see routes/web.php.
*/

Route::prefix('dev/mail')->group(function (): void {
    Route::get('/', function () {
        $keys = array_keys(devMailPreviews());

        return response()->view('dev.mail-index', ['keys' => $keys]);
    })->name('dev.mail.index');

    Route::get('/{key}', function (string $key) {
        $previews = devMailPreviews();

        abort_unless(array_key_exists($key, $previews), 404);

        $html = '';

        DB::transaction(function () use ($previews, $key, &$html): void {
            $html = $previews[$key]()->render();

            // Sample data must never persist.
            DB::rollBack();
        });

        return response($html);
    })->name('dev.mail.show');
});

/** @return array<string, callable(): Mailable> */
function devMailPreviews(): array
{
    $booking = fn (): Booking => Booking::factory()->confirmed()->create([
        'name' => 'Jess Jumper',
        'email' => 'jess@example.com',
        'price_pence' => 175000,
        'product_id' => Product::factory()->aff()->create()->id,
    ]);

    return [
        'enquiry-admin-notification' => function () {
            $enquiry = Enquiry::factory()->create(['name' => 'Sam Curious']);
            $enquiry->messages()->create(['direction' => MessageDirection::Inbound, 'body' => 'Do you run charity jumps in the summer?']);

            return new EnquiryAdminNotification($enquiry);
        },
        'enquiry-reply' => function () {
            $enquiry = Enquiry::factory()->create();
            $message = $enquiry->messages()->create(['direction' => MessageDirection::Outbound, 'body' => "We do!\n\nSummer charity dates open in March — shall I pencil you in?"]);

            return new EnquiryReplyMail($message);
        },
        'payment-received-admin' => function () use ($booking) {
            $b = $booking();
            $payment = Payment::factory()->paid()->create(['booking_id' => $b->id, 'amount_pence' => 30000, 'purpose' => 'aff_deposit']);

            return new PaymentReceivedAdminNotification($payment, $b);
        },
        'course-message' => function () {
            $course = CourseDate::factory()->create([
                'location_id' => Location::factory()->create(['name' => 'Seville, Spain'])->id,
            ]);
            $message = CourseMessage::factory()->create([
                'course_date_id' => $course->id,
                'subject' => 'Kit list and arrival details',
                'body' => "Hi everyone,\n\nOne week to go! Attached is the kit list and the arrival plan.\n\nFlights land Saturday; we meet at the dropzone café at 08:00 Monday.",
            ]);

            return new CourseMessageMail($message, 'Jess');
        },
        'voucher-gift' => function () {
            $voucher = Voucher::factory()->create([
                'purchaser_name' => 'Generous Gran',
                'recipient_name' => 'Lucky Grandkid',
                'message' => 'Happy 21st! Time to jump out of a plane. Love, Gran x',
            ]);

            return new VoucherGiftMail($voucher);
        },
        // Every editable template, rendered with representative variables.
        ...collect([
            'enquiry_acknowledgement' => ['name' => 'Sam Curious', 'reference' => 'GF-AB12CD', 'product' => 'Tandem Skydive'],
            'payment_link' => ['name' => 'Jess Jumper', 'amount' => '£300', 'description' => 'AFF course deposit', 'link' => 'https://checkout.stripe.com/example', 'reference' => 'GF-AB12CD'],
            'payment_received' => ['name' => 'Jess Jumper', 'amount' => '£300', 'product' => 'AFF Course Levels 1–8', 'reference' => 'BK-XY34ZW', 'balance_note' => 'Your remaining balance is £1,450.'],
            'booking_confirmed' => ['name' => 'Jess Jumper', 'reference' => 'BK-XY34ZW', 'product' => 'Tandem Skydive', 'date' => 'Saturday 18 July 2026, 09:00'],
            'booking_rescheduled' => ['name' => 'Jess Jumper', 'reference' => 'BK-XY34ZW', 'product' => 'Tandem Skydive', 'old_date' => 'Saturday 18 July 2026, 09:00', 'new_date' => 'Sunday 26 July 2026, 09:00'],
            'jump_reminder' => ['name' => 'Jess Jumper', 'reference' => 'BK-XY34ZW', 'product' => 'Tandem Skydive', 'date' => 'Saturday 18 July 2026, 09:00'],
            'balance_reminder' => ['name' => 'Jess Jumper', 'reference' => 'BK-XY34ZW', 'product' => 'AFF Course Levels 1–8', 'balance' => '£1,450', 'date' => 'Monday 3 August 2026'],
        ])->mapWithKeys(fn (array $vars, string $key): array => [
            'template-'.str_replace('_', '-', $key) => fn () => new TemplatedMail(EmailTemplate::findByKey($key), $vars),
        ])->all(),
    ];
}
