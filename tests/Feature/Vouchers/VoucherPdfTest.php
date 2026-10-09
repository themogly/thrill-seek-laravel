<?php

namespace Tests\Feature\Vouchers;

use App\Actions\GenerateVoucherPdf;
use App\Mail\VoucherGiftMail;
use App\Models\Voucher;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Renders the real PDF template so a Blade/CSS error in it can never ship
 * silently (the runtime path is fail-soft and would only log).
 */
class VoucherPdfTest extends TestCase
{
    public function test_generates_and_stores_the_printable_voucher(): void
    {
        Storage::fake('local');

        $voucher = Voucher::factory()->create([
            'recipient_name' => 'Lucky Grandkid',
            'message' => 'Happy 18th — time to jump out of a plane!',
        ]);

        $path = app(GenerateVoucherPdf::class)->handle($voucher);

        $this->assertSame('vouchers/'.$voucher->code.'.pdf', $path);
        $this->assertSame($path, $voucher->refresh()->pdf_path);
        Storage::disk('local')->assertExists($path);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($path));
    }

    public function test_the_gift_email_attaches_the_pdf_when_present(): void
    {
        Storage::fake('local');

        $voucher = Voucher::factory()->create();
        app(GenerateVoucherPdf::class)->handle($voucher);

        $mail = new VoucherGiftMail($voucher->refresh());

        $this->assertCount(1, $mail->attachments());
    }

    public function test_the_gift_email_still_renders_without_a_pdf(): void
    {
        Storage::fake('local');

        $mail = new VoucherGiftMail(Voucher::factory()->create());

        $this->assertSame([], $mail->attachments());
        $this->assertStringContainsString('Voucher code', $mail->render());
    }
}
