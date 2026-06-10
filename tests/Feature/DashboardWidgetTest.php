<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Filament\Widgets\BusinessStatsOverview;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    public function test_stats_overview_reports_the_business_numbers(): void
    {
        $this->actingAs(User::factory()->create());

        Payment::factory()->paid()->create(['amount_pence' => 26000, 'paid_at' => now()]);
        Payment::factory()->paid()->create(['amount_pence' => 30000, 'paid_at' => now()->subMonths(2)]);
        Enquiry::factory()->count(2)->create();
        Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addDays(2),
            'price_pence' => 100000,
        ]);

        Livewire::test(BusinessStatsOverview::class)
            ->assertOk()
            ->assertSee('Revenue this month')
            ->assertSee('£260')
            ->assertSee('New enquiries')
            ->assertSee('2')
            ->assertSee('Jumps in the next 7 days')
            ->assertSee('Outstanding balances')
            ->assertSee('£1,000');
    }
}
