<?php

namespace Tests\Feature\Mail;

use App\Filament\Widgets\MailHealthOverview;
use App\Jobs\SendCourseMessageToRecipient;
use App\Mail\AccountLoginLinkMail;
use App\Mail\TemplatedMail;
use App\Models\User;
use App\Support\MailHealth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Someone would notice: a mail job that finally fails, or a mail setup that
 * can't send, shows on the dashboard the owner opens every day. Config reads
 * only — this never sends anything.
 */
class MailHealthTest extends TestCase
{
    public function test_failed_email_jobs_in_the_last_week_are_counted_by_type(): void
    {
        $this->failJob(TemplatedMail::class, now()->subDay());
        $this->failJob(TemplatedMail::class, now()->subDays(2));
        $this->failJob(SendCourseMessageToRecipient::class, now()->subHours(3));
        $this->failJob(AccountLoginLinkMail::class, now()->subDays(9)); // too old
        $this->failJob('App\\Jobs\\SomethingElse', now()->subHour());   // not an email

        $this->assertSame(['TemplatedMail' => 2, 'CourseMessageMail' => 1], app(MailHealth::class)->failedLastWeek());
    }

    public function test_a_live_server_that_cannot_send_is_flagged(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        config([
            'mail.default' => 'log',
            'mail.from.address' => 'hello@example.com',
        ]);

        $warnings = app(MailHealth::class)->warnings();

        $this->assertCount(2, $warnings);
        $this->assertStringContainsString('not actually being sent', $warnings[0]);
        $this->assertStringContainsString('placeholder', $warnings[1]);
    }

    public function test_resend_without_a_key_is_flagged(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        config([
            'mail.default' => 'resend',
            'services.resend.key' => '',
            'mail.from.address' => 'bookings@gforceskydiving.co.uk',
        ]);

        $this->assertSame(['Emails can\'t send: the Resend API key (RESEND_API_KEY) is empty on the server.'], app(MailHealth::class)->warnings());
    }

    public function test_local_development_is_not_nagged(): void
    {
        config(['mail.default' => 'log', 'mail.from.address' => 'hello@example.com']);

        $this->assertSame([], app(MailHealth::class)->warnings());
    }

    public function test_the_dashboard_shows_mail_health(): void
    {
        $this->actingAs(User::factory()->create());
        $this->failJob(TemplatedMail::class, now()->subDay());

        Livewire::test(MailHealthOverview::class)
            ->assertSee('Failed emails (last 7 days)')
            ->assertSee('TemplatedMail: 1');
    }

    private function failJob(string $displayName, \DateTimeInterface $failedAt): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) str()->uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => $displayName], JSON_THROW_ON_ERROR),
            'exception' => 'RuntimeException: boom',
            'failed_at' => $failedAt,
        ]);
    }
}
