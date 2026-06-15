<?php

namespace Tests\Feature;

use App\Filament\Resources\Disciplines\Pages\CreateDiscipline;
use App\Filament\Resources\Instructors\Pages\EditInstructor;
use App\Models\Discipline;
use App\Models\Instructor;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplineAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_can_create_a_discipline_with_an_auto_slug(): void
    {
        Livewire::test(CreateDiscipline::class)
            ->fillForm(['name' => 'Wingsuit'])
            ->assertFormSet(['slug' => 'wingsuit'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('disciplines', ['name' => 'Wingsuit', 'slug' => 'wingsuit']);
    }

    public function test_slug_must_be_unique(): void
    {
        Discipline::factory()->create(['slug' => 'tandem']);

        Livewire::test(CreateDiscipline::class)
            ->fillForm(['name' => 'Tandem', 'slug' => 'tandem'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_admin_can_assign_multiple_disciplines_to_an_instructor(): void
    {
        $instructor = Instructor::factory()->create();
        $tandem = Discipline::factory()->create(['name' => 'Tandem', 'slug' => 'tandem']);
        $aff = Discipline::factory()->create(['name' => 'AFF', 'slug' => 'aff']);

        Livewire::test(EditInstructor::class, ['record' => $instructor->getRouteKey()])
            ->fillForm(['disciplines' => [$tandem->id, $aff->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['aff', 'tandem'],
            $instructor->fresh()->disciplines()->pluck('slug')->sort()->values()->all(),
        );
    }
}
