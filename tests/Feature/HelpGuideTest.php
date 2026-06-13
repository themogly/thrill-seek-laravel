<?php

namespace Tests\Feature;

use App\Filament\Pages\HelpGuide;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class HelpGuideTest extends TestCase
{
    public function test_admins_can_read_the_in_panel_guide(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(HelpGuide::class)
            ->assertOk()
            ->assertSee('How it all works')
            ->assertSee('Prices are in pounds')
            ->assertSee('at least 5 days', false);
    }

    public function test_the_guide_requires_login(): void
    {
        $this->get('/admin/help-guide')->assertRedirect();
    }
}
