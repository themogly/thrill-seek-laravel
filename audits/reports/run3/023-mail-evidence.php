<?php

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Voucher;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// Prompt 023 evidence: send every /dev/mail preview through the LOG mailer (into its
// own file), save the booking confirmation as .eml, and render both PDFs. All sample
// data is created inside a transaction that is rolled back, as /dev/mail does.
chdir('/Users/benhawker/Sites/thrill-seek-laravel');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! function_exists('devMailPreviews')) {
    require base_path('routes/dev.php');
}
$out = $argv[1];
config(['logging.channels.mail023' => ['driver' => 'single', 'path' => $out.'/mail-023.log'], 'mail.mailers.log.channel' => 'mail023']);
@unlink($out.'/mail-023.log');
$results = [];
foreach (devMailPreviews() as $key => $make) {
    DB::beginTransaction();
    try {
        $mailable = $make();
        $mailable->to('preview@example.com');
        Mail::mailer('log')->sendNow($mailable);
        $html = $mailable->render();
        $results[] = sprintf('%-32s sent  old-blue-as-text/fill=%d  strong=%d', $key, preg_match_all('/(?<!border-)(?<!border-left: 4px solid )(color|background-color|background):\s*#2f8de4/i', $html), substr_count(strtolower($html), '#0078cc'));
        if ($key === 'template-booking-confirmed') {
            $sent = Mail::mailer('array')->sendNow((clone $mailable));
            file_put_contents($out.'/023-booking-confirmation.eml', $sent->toString());
        }
        if ($key === 'newsletter-campaign') {
            file_put_contents($out.'/023-newsletter-campaign.html', $html);
        }
    } catch (Throwable $e) {
        $results[] = "$key FAILED ".$e->getMessage();
    } finally {
        DB::rollBack();
    }
}
// Receipt + voucher PDFs from rolled-back sample data.
DB::beginTransaction();
try {
    $booking = Booking::factory()->confirmed()->create(['name' => 'Jess Jumper', 'email' => 'jess@example.com', 'reference' => 'BK-XY34ZW', 'price_pence' => 26000, 'product_id' => Product::first()->id]);
    Payment::factory()->paid()->create(['booking_id' => $booking->id, 'amount_pence' => 26000]);
    $booking->load(['product', 'payments', 'tandemDate.location', 'courseDate.location']);
    Pdf::loadView('pdf.booking-receipt', ['booking' => $booking, 'general' => app(GeneralSettings::class), 'paidPayments' => $booking->payments->where('status', PaymentStatus::Paid)])->setPaper('a4')->save($out.'/023-receipt.pdf');
    $voucher = Voucher::factory()->create(['purchaser_name' => 'Alex Giver', 'recipient_name' => 'Sam Lucky', 'message' => 'Happy birthday — enjoy the fall!', 'product_id' => Product::first()->id]);
    Pdf::loadView('pdf.voucher', ['voucher' => $voucher->loadMissing('product'), 'general' => app(GeneralSettings::class), 'bookingUrl' => route('book.tandem')])->setPaper('a5', 'landscape')->save($out.'/023-voucher.pdf');
    $results[] = 'pdfs                             rendered';
} finally {
    DB::rollBack();
}
echo implode("\n", $results)."\n";
