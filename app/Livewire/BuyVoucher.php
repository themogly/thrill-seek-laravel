<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\StartVoucherCheckout;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\Product;
use App\Support\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class BuyVoucher extends Component
{
    use ProtectsAgainstSpam;

    public string $purchaser_name = '';

    public string $purchaser_email = '';

    public string $recipient_name = '';

    public string $message = '';

    public bool $terms = false;

    public string $paymentErrorMessage = '';

    /** @return array<string, string> */
    protected function rules(): array
    {
        return [
            'purchaser_name' => 'required|string|max:100',
            'purchaser_email' => 'required|email|max:255',
            'recipient_name' => 'required|string|max:100',
            'message' => 'nullable|string|max:500',
            'terms' => 'accepted',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'terms.accepted' => 'Please confirm you accept the voucher terms.',
        ];
    }

    public function pay(StartVoucherCheckout $startCheckout): void
    {
        if ($this->isSpam()) {
            return;
        }

        $this->ensureNotRateLimited();
        $validated = $this->validate();

        try {
            $result = $startCheckout->handle([
                'purchaser_name' => $validated['purchaser_name'],
                'purchaser_email' => $validated['purchaser_email'],
                'recipient_name' => $validated['recipient_name'],
                'message' => $validated['message'] ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('Voucher checkout could not start', ['exception' => $e->getMessage()]);

            $this->paymentErrorMessage = 'Online payment is temporarily unavailable. Nothing has been charged — get in touch and we\'ll sort a voucher directly.';

            return;
        }

        $this->redirect($result['checkout_url']);
    }

    public function getProductProperty(): ?Product
    {
        // CMS read: the voucher price mirrors the tandem product's display
        // price, so it comes from the cached gateway like any page content.
        return app(SiteContent::class)->tandemProduct();
    }

    public function render(): View
    {
        return view('livewire.buy-voucher', [
            'product' => $this->getProductProperty(),
        ]);
    }
}
