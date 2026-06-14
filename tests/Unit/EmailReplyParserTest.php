<?php

namespace Tests\Unit;

use App\Support\Inbound\EmailReplyParser;
use PHPUnit\Framework\TestCase;

class EmailReplyParserTest extends TestCase
{
    private EmailReplyParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new EmailReplyParser;
    }

    public function test_strips_gmail_style_quoted_history(): void
    {
        $raw = <<<'EMAIL'
        Yes, Saturday works great — see you then!

        On Mon, 8 Jun 2026 at 10:00, G-Force Skydiving <enquiry+abc@reply.test> wrote:
        > Hi Sam, would Saturday suit you?
        > Thanks
        EMAIL;

        $this->assertSame('Yes, Saturday works great — see you then!', $this->parser->parse($raw));
    }

    public function test_strips_signature_after_delimiter(): void
    {
        $raw = "Sounds perfect, thanks.\n\n-- \nSam Curious\n07123 456789";

        $this->assertSame('Sounds perfect, thanks.', $this->parser->parse($raw));
    }

    public function test_strips_outlook_original_message_block(): void
    {
        $raw = "Great, I'll be there.\n\n-----Original Message-----\nFrom: G-Force\nSent: Monday";

        $this->assertSame("Great, I'll be there.", $this->parser->parse($raw));
    }

    public function test_strips_mobile_signature(): void
    {
        $raw = "On my way!\n\nSent from my iPhone";

        $this->assertSame('On my way!', $this->parser->parse($raw));
    }

    public function test_keeps_a_plain_reply_untouched(): void
    {
        $raw = 'Just checking the price for two people.';

        $this->assertSame($raw, $this->parser->parse($raw));
    }
}
