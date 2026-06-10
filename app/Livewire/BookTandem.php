<?php

namespace App\Livewire;

use App\Actions\StartTandemCheckout;
use App\Exceptions\BookingUnavailableException;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\Product;
use App\Models\TandemDate;
use App\Models\Voucher;
use App\Support\Money;
use App\Support\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class BookTandem extends Component
{
    use ProtectsAgainstSpam;

    public int $step = 1;

    public ?int $slotId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $date_of_birth = '';

    public string $weight_kg = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public string $medical_notes = '';

    /** @var list<int> */
    public array $addOnIds = [];

    public string $voucherCode = '';

    public ?int $appliedVoucherId = null;

    public string $voucherMessage = '';

    public bool $terms = false;

    public string $unavailableMessage = '';

    public string $paymentErrorMessage = '';

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'slotId' => 'required|integer|exists:tandem_dates,id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'date_of_birth' => 'required|date|before:-18 years',
            'weight_kg' => 'required|numeric|min:30|max:120',
            'emergency_contact_name' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|string|max:30',
            'medical_notes' => 'nullable|string|max:2000',
            'addOnIds' => 'array',
            'addOnIds.*' => 'integer',
            'terms' => 'accepted',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'date_of_birth.before' => 'You must be at least 18 to do a tandem skydive.',
            'terms.accepted' => 'Please confirm you accept the booking terms.',
        ];
    }

    public function chooseSlot(int $slotId): void
    {
        $slot = $this->getSlotsProperty()->firstWhere('id', $slotId);

        if ($slot === null) {
            $this->unavailableMessage = 'That date is no longer available — please pick another.';

            return;
        }

        $this->slotId = $slotId;
        $this->unavailableMessage = '';
        $this->step = 2;
    }

    public function backToStep(int $step): void
    {
        $this->step = max(1, min($this->step, $step));
    }

    public function continueToReview(): void
    {
        $this->validate(collect($this->rules())->except('terms')->all(), $this->messages());

        $this->step = 3;
    }

    public function applyVoucher(): void
    {
        $voucher = Voucher::where('code', strtoupper(trim($this->voucherCode)))->first();

        if ($voucher === null || ! $voucher->isRedeemable()) {
            $this->appliedVoucherId = null;
            $this->voucherMessage = $voucher === null
                ? "We don't recognise that code — check it and try again."
                : 'That voucher is '.strtolower($voucher->display_status->getLabel()).'.';

            return;
        }

        $this->appliedVoucherId = $voucher->id;
        $this->voucherMessage = '';
    }

    public function removeVoucher(): void
    {
        $this->reset('voucherCode', 'appliedVoucherId', 'voucherMessage');
    }

    public function getAppliedVoucherProperty(): ?Voucher
    {
        if ($this->appliedVoucherId === null) {
            return null;
        }

        $voucher = Voucher::find($this->appliedVoucherId);

        return $voucher?->isRedeemable() === true ? $voucher : null;
    }

    public function getDuePenceProperty(): int
    {
        $voucher = $this->getAppliedVoucherProperty();
        $coverage = $voucher === null ? 0 : min($voucher->amount_pence, $this->getTotalPenceProperty());

        return $this->getTotalPenceProperty() - $coverage;
    }

    public function pay(StartTandemCheckout $startCheckout): void
    {
        if ($this->isSpam()) {
            return;
        }

        $this->ensureNotRateLimited();
        $this->validate();

        $slot = TandemDate::find($this->slotId);
        $product = $this->getProductProperty();

        if ($slot === null || $product === null) {
            $this->unavailableMessage = 'That date is no longer available — please pick another.';
            $this->step = 1;

            return;
        }

        try {
            $result = $startCheckout->handle($slot, $product, [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'date_of_birth' => $this->date_of_birth,
                'weight_kg' => $this->weight_kg,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'medical_notes' => $this->medical_notes,
            ], $this->addOnIds, $this->getAppliedVoucherProperty());
        } catch (BookingUnavailableException $e) {
            $this->unavailableMessage = $e->getMessage();
            $this->slotId = null;
            $this->step = 1;

            return;
        } catch (\Throwable $e) {
            Log::error('Tandem checkout could not start', ['exception' => $e->getMessage()]);

            $this->paymentErrorMessage = 'Online payment is temporarily unavailable. Nothing has been charged — please call us or send an enquiry and we\'ll book you in.';

            return;
        }

        // A voucher covering the full amount books instantly — no Stripe.
        if ($result['checkout_url'] === null) {
            $this->redirect(route('payment.success', ['booking' => $result['booking']->reference]));

            return;
        }

        $this->redirect($result['checkout_url']);
    }

    public function getProductProperty(): ?Product
    {
        // CMS read: price/add-on config comes from the cached gateway, which
        // the content observer busts on save, so it is never stale.
        return app(SiteContent::class)->tandemProduct();
    }

    /** @return EloquentCollection<int, TandemDate> */
    public function getSlotsProperty(): EloquentCollection
    {
        return TandemDate::upcoming()
            ->with('location')
            ->get()
            ->filter(fn (TandemDate $slot): bool => ! $slot->isFull())
            ->take(12)
            ->values();
    }

    public function getTotalPenceProperty(): int
    {
        $product = $this->getProductProperty();

        if ($product === null) {
            return 0;
        }

        $addOnTotal = (int) $product->addOns
            ->where('purchasable', true)
            ->whereIn('id', $this->addOnIds)
            ->sum('price_pence');

        return (int) $product->price_pence + $addOnTotal;
    }

    public function getFormattedTotalProperty(): string
    {
        return Money::formatPence($this->getTotalPenceProperty());
    }

    public function render(): View
    {
        $slots = $this->getSlotsProperty();

        return view('livewire.book-tandem', [
            'product' => $this->getProductProperty(),
            'availableSlots' => $slots,
            'selectedSlot' => $slots->firstWhere('id', $this->slotId)
                ?? TandemDate::find($this->slotId),
            'formattedTotal' => $this->getFormattedTotalProperty(),
            'appliedVoucher' => $this->getAppliedVoucherProperty(),
            'duePence' => $this->getDuePenceProperty(),
            'formattedDue' => Money::formatPence($this->getDuePenceProperty()),
        ]);
    }
}
