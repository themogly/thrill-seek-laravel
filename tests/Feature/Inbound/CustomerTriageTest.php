<?php

namespace Tests\Feature\Inbound;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerTriageTest extends TestCase
{
    public function test_customers_list_flags_and_filters_those_awaiting_a_reply(): void
    {
        $this->actingAs(User::factory()->create());

        $waiting = Customer::factory()->create();
        Enquiry::factory()->create(['customer_id' => $waiting->id, 'read_at' => null, 'status' => EnquiryStatus::CustomerReplied]);

        $quiet = Customer::factory()->create();
        Enquiry::factory()->read()->create(['customer_id' => $quiet->id, 'status' => EnquiryStatus::Replied]);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$waiting, $quiet])
            ->filterTable('has_unread', true)
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$quiet]);
    }

    public function test_unread_count_reflects_unread_enquiries(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create();
        Enquiry::factory()->count(2)->create(['customer_id' => $customer->id, 'read_at' => null]);
        Enquiry::factory()->read()->create(['customer_id' => $customer->id]);

        $loaded = Customer::query()
            ->withCount(['enquiries as unread_enquiries_count' => fn ($q) => $q->whereNull('read_at')])
            ->find($customer->id);

        $this->assertSame(2, $loaded->unread_enquiries_count);
    }
}
