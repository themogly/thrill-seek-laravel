<?php

namespace Tests\Feature;

use App\Livewire\BookTandem;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The branded date picker is a presentation swap only: the native
 * <input type="date"> must remain the value carrier (same wire:model, same
 * YYYY-MM-DD), so booking/enquiry submission + validation are unchanged. The
 * full submission paths are exercised by the booking/enquiry suites (which set
 * the wire properties directly); these assert the carrier stays wired.
 */
class DateFieldTest extends TestCase
{
    public function test_tandem_enquiry_date_fields_keep_a_native_date_carrier(): void
    {
        $html = $this->get('/tandem')->assertOk()->getContent();

        // Both the preferred-date and DOB fields render a native date input bound
        // to the same wire:model the backend validates.
        $this->assertStringContainsString('wire:model="date"', $html);
        $this->assertStringContainsString('wire:model="dob"', $html);
        $this->assertStringContainsString('type="date"', $html);
        // Constraints are passed through (future preferred date / past DOB).
        $this->assertStringContainsString('min="'.now()->toDateString().'"', $html);
        $this->assertStringContainsString('max="'.now()->subDay()->toDateString().'"', $html);
        // The desktop picker is wired (Alpine component + trigger).
        $this->assertStringContainsString('x-data="dateField()"', $html);
    }

    public function test_booking_dob_field_carries_the_18_year_max(): void
    {
        // BookTandem step 2 holds the DOB date-field; render the Livewire component
        // and confirm the native carrier + the -18yr max are present.
        $html = Livewire::test(BookTandem::class)
            ->set('step', 2)
            ->html();

        $this->assertStringContainsString('wire:model="date_of_birth"', $html);
        $this->assertStringContainsString('max="'.now()->subYears(18)->toDateString().'"', $html);
    }
}
