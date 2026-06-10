<?php

namespace Tests\Feature\Enquiries;

use App\Enums\EnquiryStatus;
use App\Enums\MessageDirection;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Mail\EnquiryReplyMail;
use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryInboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->actingAs(User::factory()->create());
    }

    public function test_viewing_an_enquiry_marks_it_read(): void
    {
        $enquiry = Enquiry::factory()->create();
        $this->assertTrue($enquiry->isUnread());

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->assertOk();

        $this->assertFalse($enquiry->refresh()->isUnread());
    }

    public function test_replying_stores_the_message_emails_the_customer_and_updates_status(): void
    {
        $enquiry = Enquiry::factory()->create();
        $enquiry->messages()->create(['direction' => MessageDirection::Inbound, 'body' => 'Hi']);

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->callAction('reply', ['body' => 'Thanks for getting in touch — yes we can!'])
            ->assertHasNoActionErrors();

        $enquiry->refresh();
        $this->assertSame(EnquiryStatus::Replied, $enquiry->status);

        $reply = $enquiry->messages()->where('direction', MessageDirection::Outbound)->sole();
        $this->assertSame('Thanks for getting in touch — yes we can!', $reply->body);
        $this->assertNotNull($reply->user_id);

        Mail::assertQueued(EnquiryReplyMail::class, fn (EnquiryReplyMail $mail) => $mail->hasTo($enquiry->email));
    }

    public function test_replying_does_not_regress_an_advanced_status(): void
    {
        $enquiry = Enquiry::factory()->create(['status' => EnquiryStatus::PaymentSent]);

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->callAction('reply', ['body' => 'Following up.']);

        $this->assertSame(EnquiryStatus::PaymentSent, $enquiry->refresh()->status);
    }

    public function test_admin_can_change_status(): void
    {
        $enquiry = Enquiry::factory()->create();

        Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
            ->callAction('changeStatus', ['status' => EnquiryStatus::Closed->value]);

        $this->assertSame(EnquiryStatus::Closed, $enquiry->refresh()->status);
    }

    public function test_guests_cannot_access_the_inbox(): void
    {
        auth()->logout();

        $this->get('/admin/enquiries')->assertRedirect();
    }
}
