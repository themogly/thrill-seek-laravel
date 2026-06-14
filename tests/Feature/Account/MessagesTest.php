<?php

namespace Tests\Feature\Account;

use App\Enums\EnquiryStatus;
use App\Enums\MessageDirection;
use App\Mail\EnquiryAdminNotification;
use App\Models\Customer;
use App\Models\Enquiry;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MessagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_customer_sees_only_their_own_threads(): void
    {
        $customer = Customer::factory()->create();
        $mine = Enquiry::factory()->create(['customer_id' => $customer->id]);
        $mine->messages()->create(['direction' => MessageDirection::Inbound, 'body' => 'My own question']);

        $theirs = Enquiry::factory()->create(['customer_id' => Customer::factory()->create()->id]);
        $theirs->messages()->create(['direction' => MessageDirection::Inbound, 'body' => 'Private to someone else']);

        $this->actingAs($customer, 'customer')
            ->get('/account/messages')
            ->assertOk()
            ->assertSee('My own question')
            ->assertDontSee('Private to someone else');
    }

    public function test_customer_can_reply_which_threads_and_flags_the_enquiry(): void
    {
        $customer = Customer::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $customer->id, 'status' => EnquiryStatus::Replied, 'read_at' => now()]);

        $this->actingAs($customer, 'customer')
            ->post('/account/messages/'.$enquiry->id.'/reply', ['body' => 'Can I bring a friend?'])
            ->assertRedirect('/account/messages/'.$enquiry->id);

        $message = $enquiry->messages()->latest('id')->first();
        $this->assertSame(MessageDirection::Inbound, $message->direction);
        $this->assertSame('Can I bring a friend?', $message->body);

        $enquiry->refresh();
        $this->assertSame(EnquiryStatus::CustomerReplied, $enquiry->status);
        $this->assertTrue($enquiry->isUnread());
        Mail::assertQueued(EnquiryAdminNotification::class);
    }

    public function test_customer_cannot_view_another_customers_thread(): void
    {
        $owner = Customer::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $owner->id]);
        $intruder = Customer::factory()->create();

        $this->actingAs($intruder, 'customer')
            ->get('/account/messages/'.$enquiry->id)
            ->assertNotFound();
    }

    public function test_customer_cannot_reply_to_another_customers_thread(): void
    {
        $owner = Customer::factory()->create();
        $enquiry = Enquiry::factory()->create(['customer_id' => $owner->id]);
        $intruder = Customer::factory()->create();

        $this->actingAs($intruder, 'customer')
            ->post('/account/messages/'.$enquiry->id.'/reply', ['body' => 'Sneaky'])
            ->assertNotFound();

        $this->assertSame(0, $enquiry->messages()->count());
    }
}
