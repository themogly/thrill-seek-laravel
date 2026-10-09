<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

/**
 * The ten-second answer to "is email working?" on a live server: one plain
 * message, sent synchronously (no queue, no worker) through the configured
 * mailer, then either "sent" or the transport's actual error. Run it after
 * every deploy that touches mail settings — see SETUP.md.
 */
class MailTest extends Command
{
    protected $signature = 'gforce:mail-test {email : Where to send the test message}';

    protected $description = 'Send one plain test email synchronously and report the transport result';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("'{$email}' is not a valid email address.");

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');

        try {
            Mail::raw(
                'This is a test email from the G-Force Skydiving website. If you can read it, sending works.',
                fn (Message $message) => $message->to($email)->subject('G-Force Skydiving — test email'),
            );
        } catch (\Throwable $e) {
            $this->error("NOT sent via '{$mailer}': {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Sent a test email to {$email} via '{$mailer}' from ".config('mail.from.address').'.');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("The '{$mailer}' mailer doesn't deliver anything — set MAIL_MAILER=resend on a live server.");
        }

        return self::SUCCESS;
    }
}
