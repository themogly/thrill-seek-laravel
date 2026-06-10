<?php

namespace App\Livewire;

use App\Actions\CreateEnquiry;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Settings\SimplePagesSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContactForm extends Component
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
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:2000',
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

        $createEnquiry->handle([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'message' => $validated['message'],
        ]);

        $this->finish();
    }

    private function finish(): void
    {
        $this->reset('name', 'email', 'phone', 'message', 'website');
        $this->dispatch('enquiry-sent', message: "Message sent! We'll be in touch.");
    }

    public function render(): View
    {
        return view('livewire.contact-form', [
            'heading' => app(SimplePagesSettings::class)->contact_form_heading,
        ]);
    }
}
