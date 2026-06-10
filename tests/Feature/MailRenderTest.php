<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MessageDirection;
use App\Mail\CourseMessageMail;
use App\Mail\EnquiryAdminNotification;
use App\Mail\EnquiryReplyMail;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\VoucherGiftMail;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\Voucher;
use Tests\TestCase;

/**
 * Renders every designed mailable to HTML. Queue/Mail fakes never render
 * Blade, so a template syntax error is invisible to the rest of the suite —
 * this caught a real one in the voucher email during the Round 4 audit.
 */
class MailRenderTest extends TestCase
{
    public function test_every_designed_mailable_renders(): void
    {
        $enquiry = Enquiry::factory()->create();
        $inbound = $enquiry->messages()->create(['direction' => MessageDirection::Inbound, 'body' => 'Hello']);
        $outbound = $enquiry->messages()->create(['direction' => MessageDirection::Outbound, 'body' => 'Hi back']);

        $booking = Booking::factory()->confirmed()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $courseMessage = CourseMessage::factory()->create([
            'course_date_id' => CourseDate::factory()->create()->id,
        ]);

        $voucherWithMessage = Voucher::factory()->create(['message' => 'Happy birthday!']);
        $voucherBare = Voucher::factory()->create(['recipient_name' => null, 'message' => null]);

        $mailables = [
            new EnquiryAdminNotification($enquiry),
            new EnquiryReplyMail($outbound),
            new PaymentReceivedAdminNotification($payment, $booking),
            new CourseMessageMail($courseMessage, 'Jess'),
            new VoucherGiftMail($voucherWithMessage),
            new VoucherGiftMail($voucherBare),
        ];

        foreach ($mailables as $mailable) {
            $html = $mailable->render();

            $this->assertNotSame('', trim($html), $mailable::class.' rendered empty output.');
            $this->assertStringContainsString('G-Force', $html, $mailable::class.' lost the brand name.');
        }
    }
}
