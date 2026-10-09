<?php

namespace Tests\Feature\Mail;

use App\Mail\AccountLoginLinkMail;
use App\Models\Customer;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

/**
 * Queued is not sent: one provider blip must not lose an email, a send queued
 * inside a transaction must wait for the commit, and a mail job that finally
 * fails must leave a trace the owner can see. Horizon's supervisor runs with
 * tries => 1, so the retry policy has to travel on the mailable itself.
 */
class QueuedMailRetriesTest extends TestCase
{
    public function test_a_queued_mailable_retries_with_backoff_after_commit(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create();

        Mail::to($customer->email)->queue(new AccountLoginLinkMail($customer, 'https://example.test/login/x'));

        Queue::assertPushed(SendQueuedMailable::class, function (SendQueuedMailable $job): bool {
            return $job->tries === 4
                && $job->backoff() === [30, 120, 600]
                && $job->afterCommit === true;
        });
    }

    public function test_a_finally_failed_mail_job_is_logged_by_type_without_the_address(): void
    {
        $customer = Customer::factory()->create(['email' => 'private@example.test']);
        $job = new SendQueuedMailable(new AccountLoginLinkMail($customer, 'https://example.test/login/x'));

        Log::shouldReceive('error')->once()->withArgs(fn (string $message, array $context): bool => $message === 'Email failed after all retries'
            && $context['mailable'] === AccountLoginLinkMail::class
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'private@example.test'));

        $job->failed(new RuntimeException('Resend timed out'));
    }
}
