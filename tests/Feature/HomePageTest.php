<?php

namespace Tests\Feature;

use App\Models\Instructor;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_teaser_renders_instructor_avatars_in_order(): void
    {
        // The homepage now shows a compact team teaser (avatars), not the full
        // roster — the avatar alt text carries the name, in sort order.
        Instructor::factory()->create(['name' => 'Zara', 'sort_order' => 2, 'photo' => '/images/z.jpg']);
        Instructor::factory()->create(['name' => 'Adam', 'sort_order' => 1, 'photo' => '/images/a.jpg']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder(['Adam', 'Zara']);
    }
}
