<?php

namespace Tests\Feature\Faqs;

use App\Enums\FaqPage;
use App\Models\Faq;
use Tests\TestCase;

class FaqDisplayTest extends TestCase
{
    public function test_a_page_shows_its_active_faqs_in_the_dom_even_when_collapsed(): void
    {
        Faq::factory()->forPage(FaqPage::Tandem)->create([
            'question' => 'Can I bring my dog?',
            'answer' => '<p>Best to leave Rex at home — the dropzone is busy.</p>',
        ]);

        $this->get('/tandem')
            ->assertOk()
            ->assertSee('Frequently Asked Questions')
            ->assertSee('Can I bring my dog?')
            // The answer is in the DOM (collapsed via CSS, never display:none/removed).
            ->assertSee('Best to leave Rex at home', false);
    }

    public function test_faqs_are_scoped_to_their_own_page(): void
    {
        Faq::factory()->forPage(FaqPage::Tandem)->create(['question' => 'Tandem only question?']);
        Faq::factory()->forPage(FaqPage::Aff)->create(['question' => 'AFF only question?']);
        Faq::factory()->forPage(FaqPage::Tandem)->inactive()->create(['question' => 'Hidden tandem question?']);

        $tandem = $this->get('/tandem');
        $tandem->assertSee('Tandem only question?');
        $tandem->assertDontSee('AFF only question?');
        $tandem->assertDontSee('Hidden tandem question?'); // inactive not shown
    }

    public function test_faqpage_jsonld_is_emitted_with_matching_questions_and_answers(): void
    {
        Faq::factory()->forPage(FaqPage::Aff)->create([
            'question' => 'How long is the course?',
            'answer' => '<p>Several days.</p><p>Weather permitting.</p>',
        ]);

        $html = $this->get('/aff')->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('"name":"How long is the course?"', $html);
        // Plain-text answer in the schema (parity with the visible rich answer), de-spaced.
        $this->assertStringContainsString('"text":"Several days. Weather permitting."', $html);
    }

    public function test_no_faq_section_or_jsonld_when_a_page_has_no_active_faqs(): void
    {
        Faq::factory()->forPage(FaqPage::Coached)->inactive()->create();

        $this->get('/coached')
            ->assertOk()
            ->assertDontSee('Frequently Asked Questions')
            ->assertDontSee('"@type":"FAQPage"', false);
    }

    public function test_faqs_render_in_sort_order(): void
    {
        Faq::factory()->forPage(FaqPage::Tandem)->create(['question' => 'Zebra question?', 'sort_order' => 2]);
        Faq::factory()->forPage(FaqPage::Tandem)->create(['question' => 'Apple question?', 'sort_order' => 1]);

        $html = $this->get('/tandem')->getContent();

        $this->assertLessThan(strpos($html, 'Zebra question?'), strpos($html, 'Apple question?'));
    }
}
