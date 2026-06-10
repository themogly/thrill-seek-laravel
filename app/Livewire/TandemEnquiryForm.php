<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateEnquiry;
use App\Enums\ProductType;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TandemEnquiryForm extends Component
{
    use ProtectsAgainstSpam;

    public string $date = '';

    public string $name = '';

    public string $address = '';

    public string $postcode = '';

    public string $dob = '';

    public string $phone = '';

    public string $email = '';

    public string $height = '';

    public string $weight = '';

    public string $sex = '';

    /** @return array<string, string> */
    protected function rules(): array
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'postcode' => 'required|string|max:20',
            'dob' => 'required|date|before:today',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'height' => 'required|numeric|min:50|max:250',
            'weight' => 'required|numeric|min:20|max:250',
            'sex' => 'required|in:male,female,other',
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
        $product = Product::active()->ofType(ProductType::Tandem)->ordered()->first();

        $createEnquiry->handle([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'preferred_date' => $validated['date'],
            'context' => [
                'address' => $validated['address'],
                'postcode' => $validated['postcode'],
                'date_of_birth' => $validated['dob'],
                'height_cm' => $validated['height'],
                'weight_kg' => $validated['weight'],
                'sex' => $validated['sex'],
            ],
        ], $product);

        $this->finish();
    }

    private function finish(): void
    {
        $this->reset('date', 'name', 'address', 'postcode', 'dob', 'phone', 'email', 'height', 'weight', 'sex', 'website');
        $this->dispatch('enquiry-sent', message: "Enquiry sent! We'll be in touch shortly.");
    }

    public function render(): View
    {
        return view('livewire.tandem-enquiry-form');
    }
}
