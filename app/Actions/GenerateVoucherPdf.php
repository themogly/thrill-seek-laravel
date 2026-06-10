<?php

namespace App\Actions;

use App\Models\Voucher;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the printable A5 gift voucher and stores it on the local (private)
 * disk so the gift email can attach it and the admin can re-download it.
 */
class GenerateVoucherPdf
{
    public function handle(Voucher $voucher): string
    {
        $pdf = Pdf::loadView('pdf.voucher', [
            'voucher' => $voucher->loadMissing('product'),
            'general' => app(GeneralSettings::class),
            'bookingUrl' => route('book.tandem'),
        ])->setPaper('a5', 'landscape');

        $path = 'vouchers/'.$voucher->code.'.pdf';

        Storage::disk('local')->put($path, $pdf->output());

        $voucher->update(['pdf_path' => $path]);

        return $path;
    }
}
