<?php

namespace Tests\Feature\Bookings;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Models\Location;
use App\Models\TandemDate;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class BookingSlotLocationTest extends TestCase
{
    public function test_jump_slot_options_include_the_location(): void
    {
        $this->actingAs(User::factory()->create());

        $devon = Location::factory()->create(['name' => 'Devon DZ']);
        TandemDate::factory()->create([
            'location_id' => $devon->id,
            'starts_at' => now()->addWeeks(2)->setTime(9, 0),
            'capacity' => 4,
        ]);

        // The option label leads with the location so identical dates at
        // different dropzones can be told apart.
        Livewire::test(CreateBooking::class)->assertSee('Devon DZ · ');
    }
}
