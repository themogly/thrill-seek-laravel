<?php

namespace Tests\Feature\Design;

use App\Livewire\AffEnquiryForm;
use App\Livewire\CoachedEnquiryForm;
use App\Livewire\ContactForm;
use App\Livewire\TandemEnquiryForm;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Consistency C-12: every enquiry form's loading label is the one shared
 * "Sending…" from <x-ui.loading-label>, never a typed "Sending...".
 */
class SendingLabelTest extends TestCase
{
    public function test_every_enquiry_form_says_sending_with_a_real_ellipsis(): void
    {
        foreach ([ContactForm::class, TandemEnquiryForm::class, AffEnquiryForm::class, CoachedEnquiryForm::class] as $form) {
            Livewire::test($form)
                ->assertSeeHtml('<span wire:loading wire:target="submit">Sending…</span>')
                ->assertDontSee('Sending...');
        }
    }
}
