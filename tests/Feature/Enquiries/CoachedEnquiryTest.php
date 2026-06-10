<?php

namespace Tests\Feature\Enquiries;

use App\Livewire\CoachedEnquiryForm;
use App\Models\Enquiry;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CoachedEnquiryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();
    }

    public function test_the_coached_page_hosts_the_enquiry_form_and_gift_box_renders_on_tandem(): void
    {
        $this->get('/coached')
            ->assertOk()
            ->assertSeeLivewire(CoachedEnquiryForm::class)
            ->assertSee('Tell us where you’re at', false);

        $this->get('/tandem')
            ->assertOk()
            ->assertSee('Give the jump of a lifetime');
    }

    public function test_a_coaching_enquiry_lands_in_the_inbox_with_context(): void
    {
        Livewire::test(CoachedEnquiryForm::class)
            ->set('name', 'Sky Flyer')
            ->set('email', 'flyer@example.com')
            ->set('phone', '07700900999')
            ->set('discipline', 'freefly')
            ->set('jumps', '150')
            ->set('licence', 'B')
            ->set('message', 'Working towards head-down.')
            ->call('submit')
            ->assertDispatched('enquiry-sent');

        $enquiry = Enquiry::sole();
        $this->assertSame('coached-skills', $enquiry->product->slug);
        $this->assertSame('Freefly', $enquiry->context['discipline']);
        $this->assertSame('150', $enquiry->context['jump_count']);
        $this->assertSame('B', $enquiry->context['licence']);
    }

    public function test_discipline_must_be_a_known_option(): void
    {
        Livewire::test(CoachedEnquiryForm::class)
            ->set('name', 'Sky Flyer')
            ->set('email', 'flyer@example.com')
            ->set('phone', '07700900999')
            ->set('discipline', 'base-jumping')
            ->set('jumps', '150')
            ->call('submit')
            ->assertHasErrors('discipline');

        $this->assertSame(0, Enquiry::count());
    }
}
