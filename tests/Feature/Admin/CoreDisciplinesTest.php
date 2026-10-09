<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Disciplines\Pages\EditDiscipline;
use App\Models\Discipline;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Tandem, AFF and Coaching pages list their instructors by discipline slug.
 * Renaming or deleting one of those three silently emptied that section, so
 * their slugs are locked and they can't be deleted. Other disciplines are free.
 */
class CoreDisciplinesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_a_page_discipline_keeps_its_slug_and_cannot_be_deleted(): void
    {
        $aff = Discipline::factory()->create(['name' => 'AFF', 'slug' => Discipline::AFF]);

        Livewire::test(EditDiscipline::class, ['record' => $aff->getRouteKey()])
            ->assertFormFieldIsDisabled('slug')
            ->assertActionDisabled(DeleteAction::class)
            ->fillForm(['name' => 'Accelerated Freefall', 'slug' => 'freefall'])
            ->call('save')
            ->callAction(DeleteAction::class);

        $aff->refresh();
        $this->assertSame(Discipline::AFF, $aff->slug);
        $this->assertSame('Accelerated Freefall', $aff->name); // the name is still the owner's
    }

    public function test_another_discipline_stays_editable_and_deletable(): void
    {
        $wingsuit = Discipline::factory()->create(['name' => 'Wingsuit', 'slug' => 'wingsuit']);

        Livewire::test(EditDiscipline::class, ['record' => $wingsuit->getRouteKey()])
            ->assertFormFieldIsEnabled('slug')
            ->assertActionEnabled(DeleteAction::class);
    }
}
