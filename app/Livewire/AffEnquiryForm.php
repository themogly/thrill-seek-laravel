<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateEnquiry;
use App\Enums\ProductType;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AffEnquiryForm extends Component
{
    use ProtectsAgainstSpam;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    /** @return array<string, string> */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
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
        $product = Product::active()->ofType(ProductType::Aff)->ordered()->first();

        $createEnquiry->handle([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'message' => $validated['message'] ?: null,
        ], $product);

        $this->finish();
    }

    private function finish(): void
    {
        $this->reset('name', 'email', 'phone', 'message', 'website');
        $this->dispatch('enquiry-sent', message: "Enquiry sent! We'll be in touch shortly.");
    }

    public function render(): View
    {
        return view('livewire.aff-enquiry-form');
    }
}
