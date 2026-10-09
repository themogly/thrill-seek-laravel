<?php

namespace App\Support;

use App\Jobs\SendCourseMessageToRecipient;
use App\Mail\CourseMessageMail;
use Illuminate\Support\Facades\DB;

/**
 * Is email working? Reads configuration and the failed-jobs table only — never
 * sends. Shown on the admin dashboard (MailHealthOverview); the active check is
 * `php artisan gforce:mail-test you@example.com`.
 */
class MailHealth
{
    /**
     * Plain-English problems that stop real email going out. Silent on a local
     * machine, where the log mailer is the point.
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        if (app()->environment('local', 'testing')) {
            return [];
        }

        $warnings = [];
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $warnings[] = "Emails are not actually being sent: the mailer is set to '{$mailer}' (MAIL_MAILER).";
        }

        if ($mailer === 'resend' && blank(config('services.resend.key'))) {
            $warnings[] = 'Emails can\'t send: the Resend API key (RESEND_API_KEY) is empty on the server.';
        }

        $from = (string) config('mail.from.address');
        if ($from === '' || preg_match('/@example\.(com|org|net|test)$/i', $from)) {
            $warnings[] = "The from-address '{$from}' is a placeholder — set MAIL_FROM_ADDRESS to an address on your verified domain.";
        }

        return $warnings;
    }

    /**
     * Email jobs that failed for good in the last 7 days, by mailable.
     *
     * @return array<string, int>
     */
    public function failedLastWeek(): array
    {
        $counts = [];

        DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDays(7))
            ->pluck('payload')
            ->each(function (string $payload) use (&$counts): void {
                $name = $this->mailableName((string) (json_decode($payload, true)['displayName'] ?? ''));

                if ($name !== null) {
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                }
            });

        arsort($counts);

        return $counts;
    }

    private function mailableName(string $displayName): ?string
    {
        if ($displayName === SendCourseMessageToRecipient::class) {
            $displayName = CourseMessageMail::class;
        }

        return str_starts_with($displayName, 'App\\Mail\\') ? class_basename($displayName) : null;
    }
}
