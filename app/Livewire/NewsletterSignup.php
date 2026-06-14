<?php

namespace App\Livewire;

use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NewsletterSignup extends Component
{
    use ProtectsAgainstSpam;

    /** "banner" (home, on the gradient) or "card" (contact sidebar / page). */
    public string $variant = 'banner';

    /** Where the signup happened — stored for the admin's context. */
    public string $source = 'footer';

    public string $email = '';

    public string $successMessage = '';

    public function subscribe(NewsletterService $newsletter): void
    {
        if ($this->isSpam()) {
            $this->finish();

            return;
        }

        $this->ensureNotRateLimited();
        $this->validateForToast();

        // Double opt-in: this records the request and emails a confirm link;
        // an already-confirmed address is a quiet no-op (no duplicate).
        $newsletter->subscribe($this->email, $this->source);

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
        $this->successMessage = 'Almost there — check your inbox to confirm your subscription.';
        $this->dispatch('enquiry-sent', message: $this->successMessage)->self();
    }

    public function render(): View
    {
        return view('livewire.newsletter-signup');
    }
}
