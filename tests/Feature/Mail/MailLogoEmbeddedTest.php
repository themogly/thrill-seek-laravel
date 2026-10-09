<?php

namespace Tests\Feature\Mail;

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
use App\Support\MailLogo;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Tests\TestCase;

/**
 * The logo travels inside every email (CID), never hot-linked from APP_URL: a
 * staging site behind basic-auth 401s the image, and many clients block remote
 * images. Checked on the message actually handed to the transport, not render().
 */
class MailLogoEmbeddedTest extends TestCase
{
    public function test_every_mailable_embeds_the_logo_instead_of_linking_it(): void
    {
        $this->seed(EmailTemplateSeeder::class);
        $enquiry = Enquiry::factory()->create();
        $outbound = $enquiry->messages()->create(['direction' => MessageDirection::Outbound, 'body' => 'Hi back']);
        $booking = Booking::factory()->confirmed()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);
        $courseMessage = CourseMessage::factory()->create(['course_date_id' => CourseDate::factory()->create()->id]);
        $subscriber = NewsletterSubscriber::factory()->create();
        $campaign = NewsletterCampaign::factory()->create(['blocks' => [
            ['type' => 'logo', 'data' => []],
            ['type' => 'heading', 'data' => ['text' => 'Big skies', 'level' => 'h1']],
        ]]);

        /** @var array<string, Mailable> $mailables */
        $mailables = [
            'templated' => new TemplatedMail(EmailTemplate::findByKey('booking_confirmed'), ['name' => 'Jess', 'reference' => $booking->reference, 'product' => 'Tandem', 'date' => 'soon', 'location' => 'Devon', 'jump_prep' => '']),
            'enquiry-admin' => new EnquiryAdminNotification($enquiry),
            'enquiry-reply' => new EnquiryReplyMail($outbound),
            'payment-admin' => new PaymentReceivedAdminNotification($payment, $booking),
            'course-message' => new CourseMessageMail($courseMessage, 'Jess'),
            'voucher' => new VoucherGiftMail(Voucher::factory()->create()),
            'newsletter-confirm' => new NewsletterConfirmationMail($subscriber),
            'newsletter' => new NewsletterCampaignMail($campaign, $subscriber),
            'login-link' => new AccountLoginLinkMail(Customer::factory()->create(), 'https://g-force.test/account/login/tok'),
        ];

        foreach ($mailables as $label => $mailable) {
            Mail::mailer('array')->to('jess@example.test')->sendNow($mailable);
            /** @var Email $email */
            $email = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
            $html = (string) $email->getHtmlBody();

            $this->assertStringContainsString('cid:'.MailLogo::CID, $html, "{$label}: logo isn't referenced by CID.");
            $this->assertStringNotContainsString('/images/email/logo.png', $html, "{$label}: logo is still hot-linked.");

            $inline = array_filter($email->getAttachments(), fn (DataPart $p): bool => $p->getFilename() === MailLogo::CID);
            $this->assertCount(1, $inline, "{$label}: the logo PNG isn't attached inline.");
            $this->assertSame('image/png', $inline[array_key_first($inline)]->getMediaType().'/'.$inline[array_key_first($inline)]->getMediaSubtype());
        }
    }

    public function test_a_newsletter_without_the_logo_block_carries_no_stray_attachment(): void
    {
        $campaign = NewsletterCampaign::factory()->create(); // no logo block
        Mail::mailer('array')->to('jess@example.test')->sendNow(new NewsletterCampaignMail($campaign, NewsletterSubscriber::factory()->create()));

        $email = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertSame([], $email->getAttachments());
    }

    public function test_previews_show_the_logo_from_its_url(): void
    {
        $campaign = NewsletterCampaign::factory()->create(['blocks' => [['type' => 'logo', 'data' => []]]]);

        $preview = (new NewsletterCampaignMail($campaign, NewsletterSubscriber::factory()->create()))->render();
        $this->assertStringContainsString(url('/images/email/logo.png'), $preview);
        // The admin builder's Preview modal shows exactly this render()
        // (EditNewsletterCampaign::renderFor), and /dev/mail renders mailables the same way.
        $this->assertStringNotContainsString('cid:', $preview);
    }
}
