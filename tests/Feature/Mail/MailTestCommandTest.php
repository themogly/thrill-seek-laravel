<?php

namespace Tests\Feature\Mail;

use Tests\TestCase;

/**
 * "Is mail working?" on a live server in ten seconds: one plain message, sent
 * synchronously through the configured mailer, and either "sent" or the
 * transport's actual error.
 */
class MailTestCommandTest extends TestCase
{
    public function test_it_sends_one_message_synchronously_and_says_so(): void
    {
        $this->artisan('gforce:mail-test', ['email' => 'owner@example.test'])
            ->expectsOutputToContain('Sent a test email to owner@example.test')
            ->assertSuccessful();

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame('owner@example.test', $messages->first()->getEnvelope()->getRecipients()[0]->getAddress());
    }

    public function test_a_transport_failure_prints_the_real_error_and_fails(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 9]);

        $this->artisan('gforce:mail-test', ['email' => 'owner@example.test'])
            ->expectsOutputToContain('NOT sent')
            ->assertFailed();
    }

    public function test_it_rejects_an_invalid_address(): void
    {
        $this->artisan('gforce:mail-test', ['email' => 'not-an-email'])->assertFailed();
    }
}
