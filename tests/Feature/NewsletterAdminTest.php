<?php

namespace Tests\Feature;

use App\Enums\NewsletterStatus;
use App\Filament\Resources\NewsletterSubscribers\Pages\CreateNewsletterSubscriber;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Widgets\BusinessStatsOverview;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class NewsletterAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_can_manually_add_a_confirmed_subscriber(): void
    {
        Livewire::test(CreateNewsletterSubscriber::class)
            ->fillForm(['email' => 'Added@Example.com', 'name' => 'Walk In'])
            ->call('create')
            ->assertHasNoFormErrors();

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('added@example.com', $subscriber->email);
        $this->assertSame(NewsletterStatus::Confirmed, $subscriber->status);
        $this->assertSame('admin', $subscriber->source);
        $this->assertNotNull($subscriber->consented_at);
    }

    public function test_list_can_search_and_filter_by_status(): void
    {
        $confirmed = NewsletterSubscriber::factory()->create(['email' => 'keen@example.com']);
        $pending = NewsletterSubscriber::factory()->pending()->create(['email' => 'maybe@example.com']);

        Livewire::test(ListNewsletterSubscribers::class)
            ->assertCanSeeTableRecords([$confirmed, $pending])
            ->searchTable('keen@example.com')
            ->assertCanSeeTableRecords([$confirmed])
            ->assertCanNotSeeTableRecords([$pending]);

        Livewire::test(ListNewsletterSubscribers::class)
            ->filterTable('status', NewsletterStatus::Pending->value)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$confirmed]);
    }

    public function test_csv_export_streams_all_subscribers(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'one@example.com']);
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

        $page = Livewire::test(ListNewsletterSubscribers::class)->instance();

        $csv = $this->captureStreamedResponse($page->exportCsv());

        $this->assertStringContainsString('one@example.com', $csv);
        $this->assertStringContainsString('gone@example.com', $csv);
        $this->assertStringContainsString('Email,Name,Status', $csv);
    }

    public function test_dashboard_reports_confirmed_subscriber_count(): void
    {
        NewsletterSubscriber::factory()->count(3)->create();
        NewsletterSubscriber::factory()->pending()->create();

        Livewire::test(BusinessStatsOverview::class)
            ->assertOk()
            ->assertSee('Newsletter subscribers')
            ->assertSee('1 awaiting confirmation');
    }

    private function captureStreamedResponse(mixed $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}
