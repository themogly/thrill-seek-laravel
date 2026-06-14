<?php

namespace Tests\Feature\Inbound;

use App\Filament\Resources\UnmatchedInboundMessages\Pages\ListUnmatchedInboundMessages;
use App\Filament\Resources\UnmatchedInboundMessages\UnmatchedInboundMessageResource;
use App\Models\UnmatchedInboundMessage;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class UnmatchedMessagesTest extends TestCase
{
    public function test_unmatched_messages_are_listed_and_can_be_marked_reviewed(): void
    {
        $this->actingAs(User::factory()->create());
        $message = UnmatchedInboundMessage::factory()->create();

        Livewire::test(ListUnmatchedInboundMessages::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$message])
            ->callTableAction('markReviewed', $message);

        $this->assertNotNull($message->refresh()->reviewed_at);
    }

    public function test_nav_badge_counts_unreviewed_messages(): void
    {
        UnmatchedInboundMessage::factory()->count(2)->create();
        UnmatchedInboundMessage::factory()->create(['reviewed_at' => now()]);

        $this->assertSame('2', UnmatchedInboundMessageResource::getNavigationBadge());
    }
}
