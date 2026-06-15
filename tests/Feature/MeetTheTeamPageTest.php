<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\Instructor;
use Tests\TestCase;

class MeetTheTeamPageTest extends TestCase
{
    public function test_page_renders_each_instructor_once_with_discipline_tags(): void
    {
        $joby = Instructor::factory()->create(['name' => 'Joby', 'sort_order' => 1, 'bio' => 'A long, detailed bio that belongs on the team page.']);
        $lucy = Instructor::factory()->create(['name' => 'Lucy', 'sort_order' => 2]);
        $tandem = Discipline::factory()->create(['name' => 'Tandem', 'slug' => 'tandem']);
        $aff = Discipline::factory()->create(['name' => 'AFF', 'slug' => 'aff']);

        $joby->disciplines()->sync([$tandem->id, $aff->id]);
        $lucy->disciplines()->sync([$tandem->id]);

        $html = $this->get('/meet-the-team')->assertOk()->getContent();

        // A multi-discipline instructor appears exactly once.
        $this->assertSame(1, substr_count($html, '>Joby<'));
        // Their longer bio renders here.
        $this->assertStringContainsString('A long, detailed bio that belongs on the team page.', $html);
        // Both of Joby's discipline tags show.
        $this->assertStringContainsString('Tandem', $html);
        $this->assertStringContainsString('AFF', $html);
        // Static grid, not a horizontal scroll rail.
        $this->assertStringNotContainsString('overflow-x-auto', $html);
    }

    public function test_page_renders_instructors_in_sort_order(): void
    {
        Instructor::factory()->create(['name' => 'Zara', 'sort_order' => 2]);
        Instructor::factory()->create(['name' => 'Adam', 'sort_order' => 1]);

        $this->get('/meet-the-team')->assertOk()->assertSeeInOrder(['Adam', 'Zara']);
    }

    public function test_page_is_reachable_from_the_why_us_dropdown(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="/meet-the-team"', $html);
    }

    public function test_page_handles_an_empty_team(): void
    {
        $this->get('/meet-the-team')
            ->assertOk()
            ->assertSee('Our team will be introduced here soon.');
    }
}
