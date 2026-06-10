<?php

namespace Tests\Feature;

use App\Filament\Resources\HallOfFame\Pages\EditHallOfFameEntry;
use App\Models\HallOfFameEntry;
use App\Models\User;
use Database\Seeders\HallOfFameSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class HallOfFameTest extends TestCase
{
    public function test_hall_of_fame_page_lists_entries_in_order(): void
    {
        HallOfFameEntry::factory()->create(['name' => 'Second Person', 'sort_order' => 2]);
        HallOfFameEntry::factory()->create(['name' => 'First Person', 'sort_order' => 1]);

        $response = $this->get('/hall-of-fame');

        $response->assertOk();
        $response->assertSeeInOrder(['First Person', 'Second Person']);
    }

    public function test_seeded_content_matches_the_original_static_page(): void
    {
        $this->seed(HallOfFameSeeder::class);

        $this->get('/hall-of-fame')
            ->assertSee('James Carter')
            ->assertSee('A Licence — Spain 2024')
            ->assertSee('/images/aff.jpg')
            ->assertSee('Anya Patel');
    }

    public function test_image_url_resolves_bundled_paths_and_uploads(): void
    {
        $bundled = HallOfFameEntry::factory()->create(['image' => '/images/tandem.jpg']);
        $uploaded = HallOfFameEntry::factory()->create(['image' => 'hall-of-fame/photo.jpg']);

        $this->assertSame('/images/tandem.jpg', $bundled->image_url);
        $this->assertStringContainsString('storage/hall-of-fame/photo.jpg', $uploaded->image_url);
    }

    public function test_admin_can_edit_an_entry(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = HallOfFameEntry::factory()->create(['milestone' => 'Old milestone']);

        Livewire::test(EditHallOfFameEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm(['milestone' => 'New milestone'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New milestone', $entry->refresh()->milestone);
    }
}
