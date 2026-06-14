<?php

namespace Tests\Feature\Inbound;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Models\Enquiry;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryTriageTest extends TestCase
{
    public function test_list_shows_triage_tabs_and_surfaces_a_customer_reply(): void
    {
        $this->actingAs(User::factory()->create());
        $replied = Enquiry::factory()->create(['status' => EnquiryStatus::CustomerReplied, 'read_at' => null]);
        $handled = Enquiry::factory()->read()->create(['status' => EnquiryStatus::Replied]);

        // Default tab is "Needs reply": the customer reply shows, the handled one doesn't.
        Livewire::test(ListEnquiries::class)
            ->assertOk()
            ->assertSee('Needs reply')
            ->assertCanSeeTableRecords([$replied])
            ->assertCanNotSeeTableRecords([$handled])
            // The "All" tab shows everything.
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$replied, $handled]);
    }

    public function test_needs_reply_tab_filters_to_new_and_customer_replied(): void
    {
        $this->actingAs(User::factory()->create());
        $replied = Enquiry::factory()->create(['status' => EnquiryStatus::CustomerReplied]);
        $new = Enquiry::factory()->create(['status' => EnquiryStatus::New]);
        $closed = Enquiry::factory()->create(['status' => EnquiryStatus::Closed]);

        Livewire::test(ListEnquiries::class)
            ->set('activeTab', 'needs_reply')
            ->assertCanSeeTableRecords([$replied, $new])
            ->assertCanNotSeeTableRecords([$closed]);
    }

    public function test_nav_badge_counts_unread_enquiries(): void
    {
        Enquiry::factory()->count(2)->create(['read_at' => null]);
        Enquiry::factory()->read()->create();

        $this->assertSame('2', EnquiryResource::getNavigationBadge());
    }
}
