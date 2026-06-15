<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\Instructor;
use Database\Seeders\ProductSeeder;
use Tests\TestCase;

class DisciplineStripTest extends TestCase
{
    public function test_course_pages_show_only_the_instructors_who_teach_that_discipline(): void
    {
        $this->seed(ProductSeeder::class);

        $tandemOnly = Instructor::factory()->create(['name' => 'Tess Tandem', 'sort_order' => 1]);
        $affOnly = Instructor::factory()->create(['name' => 'Andy Aff', 'sort_order' => 2]);
        $both = Instructor::factory()->create(['name' => 'Bo Both', 'sort_order' => 3]);

        $tandem = Discipline::factory()->create(['name' => 'Tandem', 'slug' => 'tandem']);
        $aff = Discipline::factory()->create(['name' => 'AFF', 'slug' => 'aff']);

        $tandemOnly->disciplines()->sync([$tandem->id]);
        $affOnly->disciplines()->sync([$aff->id]);
        $both->disciplines()->sync([$tandem->id, $aff->id]);

        $tandemHtml = $this->get('/tandem')->assertOk()->getContent();
        $this->assertStringContainsString('Meet your Tandem team', $tandemHtml);
        $this->assertStringContainsString('Tess Tandem', $tandemHtml);
        $this->assertStringContainsString('Bo Both', $tandemHtml);
        $this->assertStringNotContainsString('Andy Aff', $tandemHtml);

        $affHtml = $this->get('/aff')->assertOk()->getContent();
        $this->assertStringContainsString('Meet your AFF team', $affHtml);
        $this->assertStringContainsString('Andy Aff', $affHtml);
        $this->assertStringContainsString('Bo Both', $affHtml);
        $this->assertStringNotContainsString('Tess Tandem', $affHtml);
    }

    public function test_strip_is_hidden_when_no_instructor_teaches_the_discipline(): void
    {
        $this->seed(ProductSeeder::class);
        // An instructor exists but is tagged to something else.
        $instructor = Instructor::factory()->create();
        $aff = Discipline::factory()->create(['name' => 'AFF', 'slug' => 'aff']);
        $instructor->disciplines()->sync([$aff->id]);

        $this->get('/tandem')
            ->assertOk()
            ->assertDontSee('Meet your Tandem team');
    }
}
