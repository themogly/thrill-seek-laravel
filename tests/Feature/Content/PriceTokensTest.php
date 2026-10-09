<?php

namespace Tests\Feature\Content;

use App\Enums\FaqPage;
use App\Filament\Pages\Settings\ManageTandemPageSettings;
use App\Models\Faq;
use App\Models\Product;
use App\Models\User;
use App\Settings\CoachedPageSettings;
use App\Settings\SimplePagesSettings;
use App\Settings\TandemPageSettings;
use App\Support\PriceTokens;
use App\Support\SiteContent;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\ProductSeeder;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Price tokens in CMS wording (014, consistency C-7): a product price change
 * reaches every sentence that quotes it, and a token never shows raw.
 */
class PriceTokensTest extends TestCase
{
    private const RAW_TOKEN = '/\{\s*(price|deposit|addon)\s*:/i';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProductSeeder::class, FaqSeeder::class]);
    }

    public function test_changing_the_tandem_price_changes_the_faq_the_hero_and_the_faq_json_ld(): void
    {
        $this->get('/tandem')->assertOk()->assertSee('Tandems from £260'); // warm every cache first

        Product::where('slug', 'tandem-skydive')->firstOrFail()->update(['price_pence' => 28000]);

        $html = $this->get('/tandem')->assertOk()->getContent();

        // Hero subtitle (settings), FAQ accordion (Faq model), and the FAQPage JSON-LD.
        $this->assertStringContainsString('G-Force Buzz Tandems from £280', $html);
        $this->assertStringContainsString('in many cases the £280 jump cost', $html);
        $faqSchema = collect($this->jsonLd($html))->firstWhere('@type', 'FAQPage');
        $this->assertNotNull($faqSchema);
        $this->assertStringContainsString('the £280 jump cost', json_encode($faqSchema, JSON_UNESCAPED_UNICODE));
        // The page description (meta + Product JSON-LD) follows too.
        $this->assertStringContainsString('<meta name="description" content="Book a G-Force Buzz Tandem Skydive from £280.', $html);
        $this->assertStringNotContainsString('£260', $html);
    }

    public function test_add_on_and_other_product_prices_follow_their_own_records(): void
    {
        Product::where('slug', 'tandem-skydive')->firstOrFail()->addOns()->where('name', 'Outside Camera')->update(['price_pence' => 15000]);
        Product::where('slug', 'aff-course')->firstOrFail()->update(['price_pence' => 180000]);
        Product::where('slug', 'coached-skills')->firstOrFail()->update(['price_pence' => 6500]);

        $this->get('/tandem')->assertSee('an Outside Camera package is £150');
        $this->get('/aff')->assertSee('The AFF course (Levels 1–8) is £1,800');
        $this->get('/coached')->assertSee('From £65 per session');
    }

    public function test_an_unknown_token_never_reaches_public_html(): void
    {
        $tandem = app(TandemPageSettings::class);
        $tandem->hero_subtitle = 'Tandems from {price:no-such-product}.';
        $tandem->seo_description = 'From { Price : Tandem-Skydiv }.';
        $tandem->charity_note_body = 'Covers the {addon:ghost} fee.';
        $tandem->save();

        $coached = app(CoachedPageSettings::class);
        $coached->price_eyebrow = 'From {deposit:coached-skills} per session'; // known product, no deposit
        $coached->save();

        $pages = app(SimplePagesSettings::class);
        $pages->terms_body = '<p>Insurance ({addon:}) is paid on the day.</p>';
        $pages->save();

        Faq::factory()->forPage(FaqPage::Aff)->create(['answer' => '<p>It costs {price:retired-course}.</p>']);

        foreach (['/tandem', '/aff', '/coached', '/terms'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression(self::RAW_TOKEN, $html, "{$url} leaked a raw price token");
        }

        $this->get('/tandem')->assertSee('Tandems from price on enquiry.');
        $this->get('/coached')->assertSee('From price on enquiry per session');
    }

    public function test_no_seeded_public_page_shows_a_raw_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $urls = ['/', '/tandem', '/aff', '/coached', '/news', '/testimonials', '/hall-of-fame', '/meet-the-team',
            '/contact', '/privacy', '/terms', '/book/tandem', '/book/aff', '/vouchers', '/newsletter'];
        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression(self::RAW_TOKEN, $html, "{$url} shows a raw price token");
        }
    }

    public function test_token_output_equals_the_product_pages_displayed_price(): void
    {
        $tokens = app(PriceTokens::class);
        $tandem = Product::where('slug', 'tandem-skydive')->firstOrFail();
        $aff = Product::where('slug', 'aff-course')->firstOrFail();
        $camera = $tandem->addOns()->where('name', 'Outside Camera')->firstOrFail();

        // One reader per figure: the token goes through the same Money presenter as the product.
        $this->assertSame($tandem->formatted_price, $tokens->render('{price:tandem-skydive}'));
        $this->assertSame($aff->formatted_price, $tokens->render('{price:aff-course}'));
        $this->assertSame($aff->formatted_deposit, $tokens->render('{deposit:aff-course}'));
        $this->assertSame($camera->formatted_price, $tokens->render('{addon:outside-camera}'));
        $this->assertSame('£24.73', $tokens->render('{addon:p6-third-party-insurance}'));

        // …and that is the figure the product page's own pricing shows.
        $this->get('/tandem')->assertSee($tandem->formatted_price)->assertSee($camera->formatted_price);
    }

    public function test_the_admin_preview_shows_prices_and_flags_unknown_tokens(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageTandemPageSettings::class)
            ->assertSee('Preview: G-Force Buzz Tandems from <strong>£260</strong>', false)
            ->fillForm(['hero_subtitle' => 'From {price:tandem-skydiv} today'])
            ->assertSee('⚠ Unknown price {price:tandem-skydiv}', false)
            ->call('save')
            ->assertHasNoFormErrors(); // a preview, not a lock — see AdminPriceTokens
    }

    public function test_the_lookup_is_plain_integers_and_hidden_products_drop_out(): void
    {
        $map = app(SiteContent::class)->priceTokens();
        $this->assertSame(26000, $map['price:tandem-skydive']);
        $this->assertSame(30000, $map['deposit:aff-course']);
        $this->assertSame(5000, $map['addon:rebooking-fee']);
        $this->assertContainsOnlyInt($map);

        Product::where('slug', 'tandem-skydive')->firstOrFail()->update(['active' => false]);

        $this->assertArrayNotHasKey('price:tandem-skydive', app(SiteContent::class)->priceTokens());
        $this->assertSame(PriceTokens::PUBLIC_FALLBACK, app(PriceTokens::class)->render('{price:tandem-skydive}'));
    }

    /** @return list<array<string, mixed>> */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(fn (string $json): array => json_decode($json, true), $m[1]);
    }
}
