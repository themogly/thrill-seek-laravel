<?php

namespace Tests\Feature\Faqs;

use App\Enums\FaqPage;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Models\Faq;
use App\Models\User;
use App\Support\SiteContent;
use Livewire\Livewire;
use Tests\TestCase;

class FaqAdminTest extends TestCase
{
    public function test_sitecontent_returns_active_faqs_for_a_page_in_order(): void
    {
        Faq::factory()->forPage(FaqPage::Tandem)->create(['question' => 'Second?', 'sort_order' => 2]);
        Faq::factory()->forPage(FaqPage::Tandem)->create(['question' => 'First?', 'sort_order' => 1]);
        Faq::factory()->forPage(FaqPage::Tandem)->inactive()->create(['question' => 'Hidden?', 'sort_order' => 3]);
        Faq::factory()->forPage(FaqPage::Aff)->create(['question' => 'AFF one?']);

        $tandem = app(SiteContent::class)->faqs(FaqPage::Tandem);

        $this->assertSame(['First?', 'Second?'], $tandem->pluck('question')->all()); // active, ordered
        $this->assertFalse($tandem->contains('question', 'Hidden?'));                // inactive excluded
        $this->assertFalse($tandem->contains('question', 'AFF one?'));               // other page excluded
    }

    public function test_each_page_gets_exactly_its_own_active_faqs_in_order(): void
    {
        foreach (FaqPage::cases() as $i => $page) {
            Faq::factory()->forPage($page)->create(['question' => "{$page->value} two?", 'sort_order' => 2]);
            Faq::factory()->forPage($page)->create(['question' => "{$page->value} one?", 'sort_order' => 1]);
            Faq::factory()->forPage($page)->inactive()->create(['question' => "{$page->value} hidden?"]);
        }

        foreach (FaqPage::cases() as $page) {
            $expected = Faq::query()->where('page', $page->value)->where('is_active', true)->orderBy('sort_order')->pluck('question')->all();

            $this->assertSame($expected, app(SiteContent::class)->faqs($page)->pluck('question')->all(), "FAQs for {$page->value} changed.");
            $this->assertSame(["{$page->value} one?", "{$page->value} two?"], $expected);
        }
    }

    public function test_saving_a_faq_busts_the_cache(): void
    {
        Faq::factory()->forPage(FaqPage::Aff)->create(['question' => 'Original?']);
        $this->assertCount(1, app(SiteContent::class)->faqs(FaqPage::Aff)); // primes cache

        Faq::factory()->forPage(FaqPage::Aff)->create(['question' => 'Added?']);

        $this->assertCount(2, app(SiteContent::class)->faqs(FaqPage::Aff)); // observer busted it
    }

    public function test_plain_answer_strips_tags_without_running_words_together(): void
    {
        $faq = Faq::factory()->create(['answer' => '<p>First line.</p><p>Second line.</p>']);

        $this->assertSame('First line. Second line.', $faq->plainAnswer());
    }

    public function test_admin_can_list_and_create_faqs(): void
    {
        $this->actingAs(User::factory()->create());
        // List a table that has rows: the paginator only calls Builder::forPage() when there are
        // records, so an empty table hid the scope collision that 500'd the real admin.
        $faqs = Faq::factory()->count(3)->forPage(FaqPage::Tandem)->create();

        Livewire::test(ListFaqs::class)
            ->assertOk()
            ->assertCanSeeTableRecords($faqs);

        Livewire::test(CreateFaq::class)
            ->fillForm([
                'page' => FaqPage::Tandem->value,
                'question' => 'Can my nan watch?',
                'answer' => '<p>Of course — bring the whole family.</p>',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('faqs', ['question' => 'Can my nan watch?', 'page' => 'tandem']);
    }
}
