<?php

namespace Tests\Feature\Inbound;

use App\Actions\HandleInboundEmail;
use App\Contracts\InboundEmailFetcher;
use App\Enums\EnquiryStatus;
use App\Enums\MessageDirection;
use App\Jobs\ProcessInboundEmail;
use App\Models\Enquiry;
use App\Models\UnmatchedInboundMessage;
use App\Support\Inbound\InboundEmail;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class InboundWebhookTest extends TestCase
{
    private const SECRET = 'whsec_dGVzdHNlY3JldGtleWZvcnNpZ25pbmcxMjM=';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.resend.inbound_domain' => 'reply.test',
            'services.resend.webhook_secret' => self::SECRET,
        ]);
    }

    private function fakeFetcher(InboundEmail $email): void
    {
        $this->app->bind(InboundEmailFetcher::class, fn (): InboundEmailFetcher => new class($email) implements InboundEmailFetcher
        {
            public function __construct(private InboundEmail $email) {}

            public function fetch(string $emailId): ?InboundEmail
            {
                return $this->email;
            }
        });
    }

    /** @return array<string, mixed> */
    private function payload(string $to, string $id = 'email_123', string $from = 'Sam <sam@example.com>'): array
    {
        return ['type' => 'email.received', 'data' => ['email_id' => $id, 'to' => [$to], 'from' => $from, 'subject' => 'Re: enquiry']];
    }

    public function test_inbound_reply_threads_into_the_enquiry_and_flags_it(): void
    {
        $enquiry = Enquiry::factory()->create(['status' => EnquiryStatus::Replied, 'read_at' => now()]);
        $this->fakeFetcher(new InboundEmail(text: "Yes, Saturday works great!\n\nOn Mon wrote:\n> old text"));

        app(HandleInboundEmail::class)->handle($this->payload("enquiry+{$enquiry->reply_token}@reply.test"));

        $message = $enquiry->messages()->where('direction', MessageDirection::Inbound)->first();
        $this->assertNotNull($message);
        $this->assertSame('Yes, Saturday works great!', $message->body);          // quoted history stripped
        $this->assertStringContainsString('old text', (string) $message->raw_body); // raw kept
        $this->assertSame('email_123', $message->external_id);
        $this->assertSame('sam@example.com', $message->sender_email);

        $enquiry->refresh();
        $this->assertSame(EnquiryStatus::CustomerReplied, $enquiry->status);
        $this->assertNotNull($enquiry->last_customer_message_at);
        $this->assertTrue($enquiry->isUnread());
    }

    public function test_duplicate_delivery_is_idempotent(): void
    {
        $enquiry = Enquiry::factory()->create();
        $this->fakeFetcher(new InboundEmail(text: 'Hello again'));
        $payload = $this->payload("enquiry+{$enquiry->reply_token}@reply.test");

        app(HandleInboundEmail::class)->handle($payload);
        app(HandleInboundEmail::class)->handle($payload); // re-delivery

        $this->assertSame(1, $enquiry->messages()->where('direction', MessageDirection::Inbound)->count());
    }

    public function test_unknown_token_is_kept_as_unmatched(): void
    {
        $this->fakeFetcher(new InboundEmail(text: 'Who are you?'));

        app(HandleInboundEmail::class)->handle($this->payload('enquiry+deadbeeftoken@reply.test'));

        $this->assertSame(1, UnmatchedInboundMessage::where('reason', 'unknown_token')->count());
    }

    public function test_email_with_no_token_is_kept_as_unmatched(): void
    {
        $this->fakeFetcher(new InboundEmail(text: 'Generic mail'));

        app(HandleInboundEmail::class)->handle($this->payload('hello@reply.test'));

        $this->assertSame(1, UnmatchedInboundMessage::where('reason', 'no_token')->count());
    }

    public function test_auto_reply_is_not_threaded(): void
    {
        $enquiry = Enquiry::factory()->create(['status' => EnquiryStatus::Replied]);
        $this->fakeFetcher(new InboundEmail(text: 'I am on holiday', headers: ['auto-submitted' => 'auto-replied']));

        app(HandleInboundEmail::class)->handle($this->payload("enquiry+{$enquiry->reply_token}@reply.test"));

        $this->assertSame(0, $enquiry->messages()->where('direction', MessageDirection::Inbound)->count());
        $this->assertSame(0, UnmatchedInboundMessage::count());
        $this->assertSame(EnquiryStatus::Replied, $enquiry->refresh()->status);
    }

    public function test_webhook_verifies_signature_and_queues_processing(): void
    {
        Bus::fake();
        $body = json_encode($this->payload('enquiry+x@reply.test'), JSON_THROW_ON_ERROR);

        $this->call('POST', '/webhooks/resend', content: $body, server: $this->signedServer($body))
            ->assertNoContent();

        Bus::assertDispatched(ProcessInboundEmail::class);
    }

    public function test_webhook_rejects_an_invalid_signature(): void
    {
        Bus::fake();
        $body = json_encode($this->payload('enquiry+x@reply.test'), JSON_THROW_ON_ERROR);
        $server = $this->signedServer($body);
        $server['HTTP_SVIX_SIGNATURE'] = 'v1,deadbeef';

        $this->call('POST', '/webhooks/resend', content: $body, server: $server)->assertStatus(400);

        Bus::assertNotDispatched(ProcessInboundEmail::class);
    }

    /** @return array<string, string> */
    private function signedServer(string $body): array
    {
        $id = 'msg_1';
        $ts = '1700000000';
        $key = base64_decode(substr(self::SECRET, 6), true) ?: '';
        $sig = base64_encode(hash_hmac('sha256', "{$id}.{$ts}.{$body}", $key, true));

        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SVIX_ID' => $id,
            'HTTP_SVIX_TIMESTAMP' => $ts,
            'HTTP_SVIX_SIGNATURE' => "v1,{$sig}",
        ];
    }
}
