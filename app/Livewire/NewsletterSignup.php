<?php

namespace App\Livewire;

use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NewsletterSignup extends Component
{
    use ProtectsAgainstSpam;

    /** "banner" (home, on the gradient) or "card" (contact sidebar). */
    public string $variant = 'banner';

    public string $email = '';

    public string $successMessage = '';

    public function subscribe(): void
    {
        if ($this->isSpam()) {
            $this->finish();

            return;
        }

        $this->ensureNotRateLimited();
        $this->validateForToast();

        NewsletterSubscriber::subscribe($this->email);

        $this->finish();
    }

    /** @return array<string, string> */
    protected function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
        ];
    }

    private function finish(): void
    {
        $this->reset('email', 'website');
        $this->successMessage = $this->variant === 'banner'
            ? "You're in! Welcome to the G-Force list."
            : 'Subscribed!';
        $this->dispatch('enquiry-sent', message: $this->successMessage);
    }

    public function render(): View
    {
        return view('livewire.newsletter-signup');
    }
}
