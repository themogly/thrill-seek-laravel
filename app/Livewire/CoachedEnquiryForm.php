<?php

namespace App\Livewire;

use App\Actions\CreateEnquiry;
use App\Enums\ProductType;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CoachedEnquiryForm extends Component
{
    use ProtectsAgainstSpam;

    public const DISCIPLINES = [
        'belly' => 'Belly / RW',
        'freefly' => 'Freefly',
        'tracking' => 'Tracking & angle',
        'canopy' => 'Canopy piloting',
        'unsure' => 'Not sure yet',
    ];

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $discipline = '';

    public string $jumps = '';

    public string $licence = '';

    public string $message = '';

    /** @return array<string, string> */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'discipline' => 'required|in:'.implode(',', array_keys(self::DISCIPLINES)),
            'jumps' => 'required|integer|min:0|max:100000',
            'licence' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2000',
        ];
    }

    public function submit(CreateEnquiry $createEnquiry): void
    {
        if ($this->isSpam()) {
            $this->finish();

            return;
        }

        $this->ensureNotRateLimited();
        $validated = $this->validateForToast();

        // Live query (write path): the enquiry is attached to the current
        // product row, not cached display content.
        $product = Product::active()->ofType(ProductType::Coaching)->ordered()->first();

        $createEnquiry->handle([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'message' => $validated['message'] ?: null,
            'context' => [
                'discipline' => self::DISCIPLINES[$validated['discipline']],
                'jump_count' => $validated['jumps'],
                'licence' => $validated['licence'] ?: 'None yet',
            ],
        ], $product);

        $this->finish();
    }

    private function finish(): void
    {
        $this->reset('name', 'email', 'phone', 'discipline', 'jumps', 'licence', 'message', 'website');
        $this->dispatch('enquiry-sent', message: "Enquiry sent! We'll come back with a coaching plan.");
    }

    public function render(): View
    {
        return view('livewire.coached-enquiry-form');
    }
}
