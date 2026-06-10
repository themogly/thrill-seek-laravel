<?php

declare(strict_types=1);

namespace Tests\Feature\Enquiries;

use App\Livewire\AffEnquiryForm;
use App\Livewire\ContactForm;
use App\Livewire\TandemEnquiryForm;
use App\Mail\EnquiryAdminNotification;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Enquiry;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryFormsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProductSeeder::class, EmailTemplateSeeder::class]);
        Mail::fake();
    }

    public function test_contact_form_stores_an_enquiry_and_sends_emails(): void
    {
        Livewire::test(ContactForm::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('message', 'Do you run charity jumps?')
            ->call('submit')
            ->assertDispatched('enquiry-sent');

        $enquiry = Enquiry::sole();
        $this->assertSame('Jane Doe', $enquiry->name);
        $this->assertNull($enquiry->product_id);
        $this->assertStringStartsWith('GF-', $enquiry->reference);
        $this->assertSame('Do you run charity jumps?', $enquiry->messages->sole()->body);

        Mail::assertQueued(EnquiryAdminNotification::class);
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('jane@example.com')
            && str_contains($mail->renderedSubject, $enquiry->reference));
    }

    public function test_tandem_form_attaches_the_product_and_context(): void
    {
        Livewire::test(TandemEnquiryForm::class)
            ->set('date', now()->addMonth()->format('Y-m-d'))
            ->set('name', 'Sam Jumper')
            ->set('address', '1 High Street')
            ->set('postcode', 'EX1 1AA')
            ->set('dob', '1990-05-01')
            ->set('phone', '07700900000')
            ->set('email', 'sam@example.com')
            ->set('height', '180')
            ->set('weight', '80')
            ->set('sex', 'male')
            ->call('submit')
            ->assertDispatched('enquiry-sent');

        $enquiry = Enquiry::sole();
        $this->assertSame('tandem-skydive', $enquiry->product->slug);
        $this->assertSame('EX1 1AA', $enquiry->context['postcode']);
        $this->assertTrue($enquiry->preferred_date->isFuture());
    }

    public function test_aff_form_attaches_the_aff_course(): void
    {
        Livewire::test(AffEnquiryForm::class)
            ->set('name', 'Alex Learner')
            ->set('email', 'alex@example.com')
            ->set('phone', '07700900001')
            ->set('message', 'When is the next course?')
            ->call('submit')
            ->assertDispatched('enquiry-sent');

        $this->assertSame('aff-course', Enquiry::sole()->product->slug);
    }

    public function test_honeypot_blocks_bots_silently(): void
    {
        Livewire::test(ContactForm::class)
            ->set('name', 'Bot')
            ->set('email', 'bot@example.com')
            ->set('message', 'spam')
            ->set('website', 'https://spam.example')
            ->call('submit')
            ->assertDispatched('enquiry-sent');

        $this->assertSame(0, Enquiry::count());
        Mail::assertNothingQueued();
    }

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        RateLimiter::clear('enquiries:'.sha1('127.0.0.1'));

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(ContactForm::class)
                ->set('name', "Visitor {$i}")
                ->set('email', "visitor{$i}@example.com")
                ->set('message', 'Hello')
                ->call('submit');
        }

        Livewire::test(ContactForm::class)
            ->set('name', 'Visitor 6')
            ->set('email', 'visitor6@example.com')
            ->set('message', 'Hello again')
            ->call('submit')
            ->assertDispatched('enquiry-failed')
            ->assertHasErrors('name');

        $this->assertSame(5, Enquiry::count());
    }

    public function test_invalid_submissions_dispatch_a_toast_error(): void
    {
        Livewire::test(ContactForm::class)
            ->set('name', 'Jane')
            ->set('email', 'not-an-email')
            ->set('message', 'Hi')
            ->call('submit')
            ->assertDispatched('enquiry-failed')
            ->assertHasErrors('email');

        $this->assertSame(0, Enquiry::count());
    }

    public function test_enquiry_is_still_stored_when_no_acknowledgement_template_exists(): void
    {
        EmailTemplate::query()->delete();

        Livewire::test(ContactForm::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('message', 'Hello')
            ->call('submit')
            ->assertDispatched('enquiry-sent');

        $this->assertSame(1, Enquiry::count());
    }
}
