<?php

namespace Tests\Feature\Newsletter;

use App\Filament\Resources\NewsletterCampaigns\Pages\EditNewsletterCampaign;
use App\Models\NewsletterCampaign;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A sent newsletter is history: what subscribers received. It opens as "View",
 * so it must not be quietly editable and saveable.
 */
class SentCampaignReadOnlyTest extends TestCase
{
    public function test_a_sent_campaign_cannot_be_changed(): void
    {
        $this->actingAs(User::factory()->create());
        $campaign = NewsletterCampaign::factory()->sent()->create(['subject' => 'What went out']);

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->getRouteKey()])
            ->assertFormFieldIsDisabled('subject')
            ->fillForm(['subject' => 'Rewritten history'])
            ->call('save');

        $this->assertSame('What went out', $campaign->refresh()->subject);
    }

    public function test_a_draft_stays_editable(): void
    {
        $this->actingAs(User::factory()->create());
        $campaign = NewsletterCampaign::factory()->create();

        Livewire::test(EditNewsletterCampaign::class, ['record' => $campaign->getRouteKey()])
            ->assertFormFieldIsEnabled('subject');
    }
}
