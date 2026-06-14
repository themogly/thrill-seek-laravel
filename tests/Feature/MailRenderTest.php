<?php

namespace Tests\Feature;

use App\Enums\MessageDirection;
use App\Mail\AccountLoginLinkMail;
use App\Mail\CourseMessageMail;
use App\Mail\EnquiryAdminNotification;
use App\Mail\EnquiryReplyMail;
use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterConfirmationMail;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\TemplatedMail;
use App\Mail\VoucherGiftMail;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Enquiry;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\Voucher;
use App\Settings\JumpPrepSettings;
use Database\Seeders\EmailTemplateSeeder;
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

        $subscriber = NewsletterSubscriber::factory()->pending()->create();
        $campaign = NewsletterCampaign::factory()->sent()->create();

        $mailables = [
            new EnquiryAdminNotification($enquiry),
            new EnquiryReplyMail($outbound),
            new PaymentReceivedAdminNotification($payment, $booking),
            new CourseMessageMail($courseMessage, 'Jess'),
            new VoucherGiftMail($voucherWithMessage),
            new VoucherGiftMail($voucherBare),
            new NewsletterConfirmationMail($subscriber),
            new NewsletterCampaignMail($campaign, $subscriber),
            new AccountLoginLinkMail(Customer::factory()->create(['name' => 'Jess Jumper']), 'https://g-force.test/account/login/tok'),
        ];

        foreach ($mailables as $mailable) {
            $html = $mailable->render();

            $this->assertNotSame('', trim($html), $mailable::class.' rendered empty output.');
            $this->assertStringContainsString('G-Force', $html, $mailable::class.' lost the brand name.');
        }
    }

    public function test_templated_emails_render_through_the_shared_greeting_and_signoff(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $html = (new TemplatedMail(EmailTemplate::findByKey('booking_confirmed'), [
            'name' => 'Jess Jumper',
            'reference' => 'BK-XY34ZW',
            'product' => 'Tandem Skydive',
            'date' => 'Saturday 18 July 2026, 09:00',
            'location' => 'Devon',
            'jump_prep' => app(JumpPrepSettings::class)->emailBlock(),
        ]))->render();

        // Greeting + sign-off come from the shared layout, once.
        $this->assertStringContainsString('Hi Jess Jumper,', $html);
        $this->assertStringContainsString('The G-Force team', $html);
        // The unique body and the single-source pre-jump block are present.
        $this->assertStringContainsString('your booking for Tandem Skydive is confirmed', $html);
        $this->assertStringContainsString('Before your jump', $html);

        // The greeting/sign-off are NOT duplicated inside the editable template body.
        $body = EmailTemplate::findByKey('booking_confirmed')->body;
        $this->assertStringNotContainsString('Hi {{ name }}', $body);
        $this->assertStringNotContainsString('Blue skies', $body);
    }
}
