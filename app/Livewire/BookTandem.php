<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\StartTandemCheckout;
use App\Enums\ProductType;
use App\Exceptions\BookingUnavailableException;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\AvailabilitySlot;
use App\Models\Product;
use App\Support\Money;
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

    public bool $terms = false;

    public string $unavailableMessage = '';

    public string $paymentErrorMessage = '';

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'slotId' => 'required|integer|exists:availability_slots,id',
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

    public function pay(StartTandemCheckout $startCheckout): void
    {
        if ($this->isSpam()) {
            return;
        }

        $this->ensureNotRateLimited();
        $this->validate();

        $slot = AvailabilitySlot::find($this->slotId);
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
            ], $this->addOnIds);
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

        $this->redirect($result['checkout_url']);
    }

    public function getProductProperty(): ?Product
    {
        return Product::active()->ofType(ProductType::Tandem)->ordered()->with('addOns')->first();
    }

    /** @return EloquentCollection<int, AvailabilitySlot> */
    public function getSlotsProperty(): EloquentCollection
    {
        return AvailabilitySlot::upcoming()
            ->get()
            ->filter(fn (AvailabilitySlot $slot): bool => ! $slot->isFull())
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
                ?? AvailabilitySlot::find($this->slotId),
            'formattedTotal' => $this->getFormattedTotalProperty(),
        ]);
    }
}
