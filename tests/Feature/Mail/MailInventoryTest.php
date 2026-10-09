<?php

namespace Tests\Feature\Mail;

use App\Jobs\SendCourseMessageToRecipient;
use App\Mail\AccountLoginLinkMail;
use App\Mail\CourseMessageMail;
use App\Mail\EnquiryAdminNotification;
use App\Mail\EnquiryReplyMail;
use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterConfirmationMail;
use App\Mail\PaymentReceivedAdminNotification;
use App\Mail\QueuedMailable;
use App\Mail\TemplatedMail;
use App\Mail\VoucherGiftMail;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use ReflectionClass;
use Tests\TestCase;
use Tests\Unit\Architecture\SourceFiles;

/**
 * The email audit's structural guard (audits/reports/email-audit.md). It walks
 * the whole class of mail, not one instance:
 *
 * 1. every mailable has a production caller (not just app/Mail or /dev/mail);
 * 2. every queued mailable extends QueuedMailable (retries, after-commit, failed hook);
 * 3. the app is single-locale — or every Mail::to() pins a locale before sending;
 * 4. every UI string that claims an email went out is paired, here, with the
 *    mailable that makes it true. A new claim fails the build until it's paired.
 */
class MailInventoryTest extends TestCase
{
    /**
     * Mailables deliberately NOT extending QueuedMailable, with the reason.
     *
     * @var array<class-string, string>
     */
    private const NOT_QUEUED_MAILABLE = [
        CourseMessageMail::class => 'Sent synchronously inside SendCourseMessageToRecipient, which is the retry unit and carries the same rules (asserted below).',
    ];

    /**
     * Every UI sentence that claims (or describes) an email being sent, keyed
     * "file :: phrase", paired with the mailable(s) that make it true — or null
     * with a reason when the sentence isn't about an email.
     *
     * @var array<string, list<class-string>|string>
     */
    private const CLAIMS = [
        // Public site
        'app/Http/Controllers/Account/LoginController.php :: we\'ve emailed you a secure sign-in link' => [AccountLoginLinkMail::class],
        'app/Livewire/NewsletterSignup.php :: check your inbox to confirm' => [NewsletterConfirmationMail::class],
        'resources/views/pages/payment-success.blade.php :: check your inbox for the confirmation' => [TemplatedMail::class],
        'resources/views/pages/payment-success.blade.php :: A confirmation email with your reference is on its way' => [TemplatedMail::class],
        'resources/views/pages/payment-success.blade.php :: A confirmation email is on its way' => [TemplatedMail::class],
        // Also shown after a gift-voucher purchase, where the email is the voucher (see E-3 in the email audit).
        'resources/views/pages/payment-success.blade.php :: watch your inbox' => [TemplatedMail::class, VoucherGiftMail::class],
        'resources/views/livewire/buy-voucher.blade.php :: The voucher is emailed here' => [VoucherGiftMail::class],
        'app/Livewire/ContactForm.php :: Message sent!' => 'About the enquiry being stored, not an email.',
        'app/Livewire/TandemEnquiryForm.php :: Enquiry sent!' => 'About the enquiry being stored, not an email.',
        'app/Livewire/AffEnquiryForm.php :: Enquiry sent!' => 'About the enquiry being stored, not an email.',
        'app/Livewire/CoachedEnquiryForm.php :: Enquiry sent!' => 'About the enquiry being stored, not an email.',
        'resources/views/livewire/buy-voucher.blade.php :: Voucher request sent' => 'About the request (enquiry) being stored.',
        'resources/views/livewire/book-aff.blade.php :: Course request sent' => 'About the request (enquiry) being stored.',
        'resources/views/livewire/book-tandem.blade.php :: Booking request sent' => 'About the request (enquiry) being stored.',
        // Admin
        'app/Filament/Resources/Enquiries/Pages/ViewEnquiry.php :: Sent to the customer by email' => [EnquiryReplyMail::class],
        'app/Filament/Resources/Enquiries/Pages/ViewEnquiry.php :: Reply sent' => [EnquiryReplyMail::class],
        'app/Filament/Resources/Enquiries/Pages/ViewEnquiry.php :: Payment link sent' => [TemplatedMail::class],
        'app/Filament/Resources/Enquiries/Pages/ViewEnquiry.php :: Emailed to' => [EnquiryReplyMail::class, TemplatedMail::class],
        'app/Filament/Resources/Bookings/Tables/BookingsTable.php :: The customer has been emailed' => [TemplatedMail::class],
        'app/Filament/Resources/Bookings/Tables/BookingsTable.php :: email not sent' => 'Reports a failed send.',
        'app/Filament/Resources/Bookings/Tables/BookingsTable.php :: could not be sent' => 'Reports a failed send.',
        'app/Filament/Resources/Bookings/Tables/BookingsTable.php :: No email sent' => 'Reports that no email was sent.',
        'app/Filament/Resources/Vouchers/VoucherResource.php :: Voucher emailed' => [VoucherGiftMail::class],
        'app/Filament/Resources/Vouchers/VoucherResource.php :: The customer has been emailed' => [TemplatedMail::class, PaymentReceivedAdminNotification::class],
        'app/Filament/Resources/Vouchers/VoucherResource.php :: email not sent' => 'Reports a failed send.',
        'app/Filament/Resources/Vouchers/VoucherResource.php :: could not be sent' => 'Reports a failed send.',
        'app/Filament/Resources/Vouchers/VoucherResource.php :: No email sent' => 'Reports that no email was sent.',
        'app/Filament/Resources/Bookings/Pages/CreateBooking.php :: The customer has been emailed' => [TemplatedMail::class],
        'app/Filament/Resources/Bookings/Pages/CreateBooking.php :: email not sent' => 'Reports a failed send.',
        'app/Filament/Resources/Bookings/Pages/CreateBooking.php :: could not be sent' => 'Reports a failed send.',
        'app/Filament/Resources/Vouchers/VoucherResource.php :: Nothing was sent' => 'Reports a failed send.',
        'app/Filament/Resources/NewsletterCampaigns/Pages/EditNewsletterCampaign.php :: Test sent' => [NewsletterCampaignMail::class],
        'app/Filament/Resources/NewsletterCampaigns/Pages/EditNewsletterCampaign.php :: has been sent and can’t be changed' => [NewsletterCampaignMail::class],
        'app/Filament/Resources/CourseDates/RelationManagers/RemindersRelationManager.php :: Sent to everyone on the course' => [CourseMessageMail::class],
        'app/Filament/Resources/CourseDates/RelationManagers/MessagesRelationManager.php :: Sent by' => 'A column label on the sent-message history.',
        // Help guide
        'app/Filament/Pages/HelpGuide.php :: the customer is emailed the new date' => [TemplatedMail::class],
        'app/Filament/Pages/HelpGuide.php :: your reply is emailed to the customer' => [EnquiryReplyMail::class],
        'app/Filament/Pages/HelpGuide.php :: emailed a code + printable PDF' => [VoucherGiftMail::class],
        'app/Filament/Pages/HelpGuide.php :: Sent newsletters are kept as history' => [NewsletterCampaignMail::class],
        'app/Filament/Pages/HelpGuide.php :: Reminders are sent automatically' => [TemplatedMail::class, CourseMessageMail::class],
        'app/Filament/Pages/HelpGuide.php :: and you’re emailed' => [EnquiryAdminNotification::class],
        'app/Filament/Pages/HelpGuide.php :: Confirmations, receipts, sign-in links and reminders all go out by email' => [TemplatedMail::class, PaymentReceivedAdminNotification::class, AccountLoginLinkMail::class],
    ];

    // Hyphen-bounded so identifiers like the `enquiry-sent` event don't count.
    private const CLAIM_PATTERN = '/(?<![\w-])(e-?mailed|check your (inbox|email)|watch your inbox|you\'?ll (receive|get)|on its way|we\'?ll email|go out by email|sent)(?![\w-])/i';

    public function test_every_mailable_has_a_production_caller(): void
    {
        $production = $this->productionSource();

        foreach ($this->mailables() as $class) {
            $short = class_basename($class);
            $this->assertMatchesRegularExpression(
                '/new\s+'.$short.'\s*\(/',
                $production,
                "{$short} is never sent outside app/Mail and the /dev/mail preview — dead code, or a missing send?",
            );
        }
    }

    public function test_every_queued_mailable_extends_the_base_that_carries_the_retry_rules(): void
    {
        foreach ($this->mailables() as $class) {
            if (isset(self::NOT_QUEUED_MAILABLE[$class])) {
                continue;
            }

            $this->assertTrue(
                is_subclass_of($class, QueuedMailable::class),
                class_basename($class).' must extend QueuedMailable (retries, after-commit, failed hook) — or be listed in NOT_QUEUED_MAILABLE with a reason.',
            );
        }

        // The exemption must not be silent: the job that sends it carries the same rules.
        $job = new ReflectionClass(SendCourseMessageToRecipient::class);
        $this->assertTrue($job->implementsInterface(ShouldQueueAfterCommit::class));
        $defaults = $job->getDefaultProperties();
        $this->assertGreaterThanOrEqual(3, $defaults['tries']);
        $this->assertNotEmpty($defaults['backoff']);
        $this->assertTrue($job->hasMethod('failed'));
    }

    public function test_the_app_is_single_locale_or_every_send_pins_a_locale(): void
    {
        $locales = collect(glob(base_path('lang/*'), GLOB_ONLYDIR) ?: [])
            ->map(fn (string $dir): string => basename($dir))
            ->push((string) config('app.locale'))
            ->unique();

        if ($locales->count() <= 1) {
            // One language: the worker's default IS the recipient's. Pinning would be ceremony.
            $this->assertSame((string) config('app.locale'), (string) config('app.fallback_locale'));

            return;
        }

        // More than one language: a queued send without ->locale() goes out in the default.
        $unpinned = [];
        foreach ($this->appPhpFiles() as $path) {
            $source = (string) file_get_contents($path);
            preg_match_all('/Mail::to\(.*?->(queue|send|sendNow|later)\(/s', $source, $calls, PREG_OFFSET_CAPTURE);
            foreach ($calls[0] as [$call, $offset]) {
                if (! str_contains($call, '->locale(')) {
                    $unpinned[] = SourceFiles::relative($path).':'.(substr_count($source, "\n", 0, $offset) + 1);
                }
            }
        }

        $this->assertSame([], $unpinned, 'The app has '.$locales->count()." locales, so every Mail::to() must pin ->locale(...):\n".implode("\n", $unpinned));
    }

    public function test_every_ui_claim_that_an_email_went_out_is_paired_with_its_mailable(): void
    {
        $found = $this->uiClaims();
        $this->assertGreaterThan(20, count($found), 'Found almost no UI claims — the scanner is broken.');

        $unpaired = [];
        $used = [];
        foreach ($found as [$file, $text]) {
            $key = $this->pairingFor($file, $text);
            if ($key === null) {
                $unpaired[] = "{$file} :: {$text}";
            } else {
                $used[$key] = true;
            }
        }

        $this->assertSame([], $unpaired, "New UI text claims an email was sent. Pair it in MailInventoryTest::CLAIMS with the mailable that makes it true (and make sure that send really happens in the same path):\n".implode("\n", $unpaired));

        $stale = array_diff(array_keys(self::CLAIMS), array_keys($used));
        $this->assertSame([], array_values($stale), 'These CLAIMS entries no longer match any UI text — remove them.');

        foreach (self::CLAIMS as $key => $mailables) {
            foreach (is_array($mailables) ? $mailables : [] as $mailable) {
                $this->assertTrue(is_subclass_of($mailable, Mailable::class), "{$key} is paired with {$mailable}, which isn't a mailable.");
            }
        }
    }

    /**
     * @return list<class-string<Mailable>>
     */
    private function mailables(): array
    {
        $classes = [];
        foreach (SourceFiles::under('app/Mail', '.php') as $path) {
            $class = 'App\\Mail\\'.basename($path, '.php');
            if (class_exists($class) && is_subclass_of($class, Mailable::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }
        $this->assertGreaterThanOrEqual(9, count($classes), 'Found fewer mailables than exist — the scan is broken.');

        return $classes;
    }

    private function productionSource(): string
    {
        $source = '';
        foreach ([...$this->appPhpFiles(), ...SourceFiles::under('routes', '.php')] as $path) {
            if (str_contains($path, '/app/Mail/') || str_ends_with($path, '/routes/dev.php')) {
                continue;
            }
            $source .= file_get_contents($path)."\n";
        }

        return $source;
    }

    /**
     * @return list<string>
     */
    private function appPhpFiles(): array
    {
        return SourceFiles::under('app', '.php');
    }

    /**
     * UI text matching the claim pattern: PHP string literals in the screens and
     * controllers, and text/attribute values in the non-mail Blade views.
     *
     * @return list<array{string, string}>
     */
    private function uiClaims(): array
    {
        $claims = [];

        foreach (['app/Livewire', 'app/Filament', 'app/Http'] as $directory) {
            foreach (SourceFiles::under($directory, '.php') as $path) {
                foreach (token_get_all((string) file_get_contents($path)) as $token) {
                    if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                        $this->collect($claims, SourceFiles::relative($path), $token[1]);
                    }
                }
            }
        }

        foreach (SourceFiles::under('resources/views') as $path) {
            $relative = SourceFiles::relative($path);
            if (str_starts_with($relative, 'resources/views/mail/') || str_starts_with($relative, 'resources/views/vendor/')) {
                continue; // the emails themselves
            }
            $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($path));
            foreach (preg_split('/[<>"]|\{\{|\}\}/', $source) ?: [] as $chunk) {
                $this->collect($claims, $relative, $chunk);
            }
        }

        return $claims;
    }

    /**
     * @param  list<array{string, string}>  $claims
     */
    private function collect(array &$claims, string $file, string $text): void
    {
        $text = trim((string) preg_replace('/\s+/', ' ', trim($text, " \t\n\r'\"")));

        // A sentence or label, not an identifier: has a space and matches the pattern.
        if (str_contains($text, ' ') && preg_match(self::CLAIM_PATTERN, $text)) {
            $claims[] = [$file, $text];
        }
    }

    private function pairingFor(string $file, string $text): ?string
    {
        foreach (array_keys(self::CLAIMS) as $key) {
            [$claimFile, $phrase] = explode(' :: ', $key, 2);
            if ($claimFile === $file && str_contains($text, $phrase)) {
                return $key;
            }
        }

        return null;
    }
}
