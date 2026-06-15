<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\Instructor;
use App\Support\SiteContent;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\InstructorSeeder;
use Tests\TestCase;

class InstructorDisciplinesTest extends TestCase
{
    public function test_one_instructor_holds_multiple_disciplines_without_duplication(): void
    {
        $instructor = Instructor::factory()->create();
        $tandem = Discipline::factory()->create(['slug' => 'tandem']);
        $aff = Discipline::factory()->create(['slug' => 'aff']);

        $instructor->disciplines()->sync([$tandem->id, $aff->id]);

        $this->assertSame(['aff', 'tandem'], $instructor->disciplines()->pluck('slug')->sort()->values()->all());

        // Queried from the discipline side, the instructor appears exactly once.
        $this->assertSame(1, $aff->instructors()->where('instructors.id', $instructor->id)->count());
    }

    public function test_discipline_filter_returns_the_right_instructors_once(): void
    {
        $multi = Instructor::factory()->create(['sort_order' => 1]);
        $single = Instructor::factory()->create(['sort_order' => 2]);
        $tandem = Discipline::factory()->create(['slug' => 'tandem']);
        $aff = Discipline::factory()->create(['slug' => 'aff']);

        $multi->disciplines()->sync([$tandem->id, $aff->id]);
        $single->disciplines()->sync([$tandem->id]);

        $content = app(SiteContent::class);

        $this->assertSame([$multi->id, $single->id], $content->instructorsForDiscipline('tandem')->pluck('id')->all());
        $this->assertSame([$multi->id], $content->instructorsForDiscipline('aff')->pluck('id')->all());
    }

    public function test_cached_gateway_attaches_the_disciplines_relation(): void
    {
        $instructor = Instructor::factory()->create();
        $tandem = Discipline::factory()->create(['name' => 'Tandem', 'slug' => 'tandem']);
        $instructor->disciplines()->sync([$tandem->id]);

        $cached = app(SiteContent::class)->instructors();

        $this->assertCount(1, $cached);
        $this->assertTrue($cached->first()->relationLoaded('disciplines'));
        $this->assertSame('Tandem', $cached->first()->disciplines->first()->name);
    }

    public function test_saving_a_discipline_busts_the_instructor_cache(): void
    {
        $instructor = Instructor::factory()->create();
        $tandem = Discipline::factory()->create(['name' => 'Tandem', 'slug' => 'tandem']);
        $instructor->disciplines()->sync([$tandem->id]);

        $content = app(SiteContent::class);
        $this->assertSame('Tandem', $content->instructors()->first()->disciplines->first()->name);

        $tandem->update(['name' => 'Tandem Jumps']);

        $this->assertSame('Tandem Jumps', $content->instructors()->first()->disciplines->first()->name);
    }

    public function test_seeder_creates_three_disciplines_and_assigns_them(): void
    {
        $this->seed(InstructorSeeder::class);
        $this->seed(DisciplineSeeder::class);

        $this->assertSame(['aff', 'coaching', 'tandem'], Discipline::pluck('slug')->sort()->values()->all());

        $joby = Instructor::where('name', 'like', 'Joby%')->firstOrFail();
        $this->assertSame(['aff', 'coaching', 'tandem'], $joby->disciplines()->pluck('slug')->sort()->values()->all());

        $lucy = Instructor::where('name', 'like', 'Lucy%')->firstOrFail();
        $this->assertSame(['tandem'], $lucy->disciplines()->pluck('slug')->all());
    }
}
