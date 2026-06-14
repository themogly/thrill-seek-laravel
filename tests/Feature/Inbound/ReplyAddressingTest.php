<?php

namespace Tests\Feature\Inbound;

use App\Mail\EnquiryReplyMail;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use Tests\TestCase;

class ReplyAddressingTest extends TestCase
{
    public function test_each_enquiry_gets_a_unique_unguessable_reply_token(): void
    {
        $a = Enquiry::factory()->create();
        $b = Enquiry::factory()->create();

        $this->assertNotEmpty($a->reply_token);
        $this->assertSame(32, strlen($a->reply_token));
        $this->assertNotSame($a->reply_token, $b->reply_token);
        // Mixed-case alphanumeric (Str::random), not the small guessable id.
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $a->reply_token);
    }

    public function test_reply_to_address_uses_the_token_when_an_inbound_domain_is_set(): void
    {
        config(['services.resend.inbound_domain' => 'reply.gforce.test']);
        $enquiry = Enquiry::factory()->create();

        $this->assertSame(
            "enquiry+{$enquiry->reply_token}@reply.gforce.test",
            $enquiry->replyToAddress(),
        );
    }

    public function test_reply_to_address_is_null_without_an_inbound_domain(): void
    {
        config(['services.resend.inbound_domain' => null]);

        $this->assertNull(Enquiry::factory()->create()->replyToAddress());
    }

    public function test_token_can_be_extracted_from_an_address_and_resolved(): void
    {
        $enquiry = Enquiry::factory()->create();

        $token = Enquiry::extractReplyToken("Customer <enquiry+{$enquiry->reply_token}@reply.gforce.test>");

        $this->assertSame($enquiry->reply_token, $token);
        $this->assertTrue($enquiry->is(Enquiry::findByReplyToken((string) $token)));
        $this->assertNull(Enquiry::extractReplyToken('someone@example.com'));
        $this->assertNull(Enquiry::findByReplyToken('nope'));
    }

    public function test_enquiry_reply_email_sets_the_per_enquiry_reply_to(): void
    {
        config(['services.resend.inbound_domain' => 'reply.gforce.test']);
        $enquiry = Enquiry::factory()->create();
        $message = EnquiryMessage::factory()->create(['enquiry_id' => $enquiry->id]);

        $envelope = (new EnquiryReplyMail($message))->envelope();

        $this->assertSame("enquiry+{$enquiry->reply_token}@reply.gforce.test", $envelope->replyTo[0]->address);
    }
}
