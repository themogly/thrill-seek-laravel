<?php

namespace Tests\Feature;

use App\Models\Instructor;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_instructors_in_order(): void
    {
        Instructor::factory()->create(['name' => 'Zara', 'sort_order' => 2]);
        Instructor::factory()->create(['name' => 'Adam', 'sort_order' => 1]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder(['Adam', 'Zara']);
    }
}
