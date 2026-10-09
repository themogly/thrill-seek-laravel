<?php

namespace Tests\Feature\Newsletter;

use App\Enums\NewsletterStatus;
use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterConfirmationMail;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Prompt 026 (email audit follow-up E-1): every newsletter carries RFC 8058
 * one-click unsubscribe headers, and the mail client's POST to that signed URL
 * unsubscribes — no session, no CSRF token, nothing to click through.
 */
class ListUnsubscribeTest extends TestCase
{
    public function test_a_newsletter_carries_the_one_click_headers_in_the_sent_message(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $headers = $this->sentHeaders(new NewsletterCampaignMail(NewsletterCampaign::factory()->create(['rendered_html' => '<p>Hi</p>']), $subscriber));

        $this->assertMatchesRegularExpression('#^<https?://[^>]+/newsletter/unsubscribe/'.$subscriber->id.'\?signature=[a-f0-9]+>$#', (string) $headers['list-unsubscribe']);
        $this->assertSame('List-Unsubscribe=One-Click', $headers['list-unsubscribe-post']);
    }

    public function test_posting_to_the_header_url_unsubscribes_without_a_csrf_token_and_is_idempotent(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();
        $url = $this->headerUrl($subscriber);

        $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertOk()->assertSee('Unsubscribed.');
        $this->assertSame(NewsletterStatus::Unsubscribed, $subscriber->fresh()->status);

        $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertOk();
        $this->assertSame(NewsletterStatus::Unsubscribed, $subscriber->fresh()->status);
    }

    public function test_a_tampered_url_is_refused_and_changes_nothing(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();
        $other = NewsletterSubscriber::factory()->create();
        $tampered = str_replace('/unsubscribe/'.$subscriber->id.'?', '/unsubscribe/'.$other->id.'?', $this->headerUrl($subscriber));

        $this->post($tampered)->assertForbidden();
        $this->post(route('newsletter.unsubscribe.one-click', $other))->assertForbidden();

        $this->assertSame(NewsletterStatus::Confirmed, $other->fresh()->status);
    }

    public function test_the_footer_link_still_unsubscribes_with_a_get(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $this->get($this->headerUrl($subscriber))->assertOk()->assertSee('Unsubscribed');
        $this->assertSame(NewsletterStatus::Unsubscribed, $subscriber->fresh()->status);
    }

    public function test_confirmation_and_transactional_mail_carry_no_list_unsubscribe(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        foreach ([
            new NewsletterConfirmationMail(NewsletterSubscriber::factory()->pending()->create()),
            new TemplatedMail(EmailTemplate::findByKey('booking_confirmed'), ['name' => 'Jess', 'reference' => 'BK-1', 'product' => 'Tandem', 'date' => 'Saturday', 'location' => 'Devon', 'jump_prep' => '']),
        ] as $mailable) {
            $this->assertArrayNotHasKey('list-unsubscribe', $this->sentHeaders($mailable), $mailable::class);
        }
    }

    private function headerUrl(NewsletterSubscriber $subscriber): string
    {
        $headers = $this->sentHeaders(new NewsletterCampaignMail(NewsletterCampaign::factory()->create(['rendered_html' => '<p>Hi</p>']), $subscriber));

        return trim((string) $headers['list-unsubscribe'], '<>');
    }

    /** @return array<string, string> lower-cased header name => value, from the real MIME message */
    private function sentHeaders(Mailable $mailable): array
    {
        $sent = Mail::mailer('array')->to('reader@example.com')->sendNow($mailable);

        $headers = [];
        foreach ($sent->getOriginalMessage()->getHeaders()->all() as $header) {
            $headers[strtolower($header->getName())] = $header->getBodyAsString();
        }

        return $headers;
    }
}
