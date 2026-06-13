<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\Pages\BookingCalendar;
use App\Filament\Resources\CourseDates\Pages\EditCourseDate;
use App\Filament\Resources\CourseDates\RelationManagers\MessagesRelationManager;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use Tests\TestCase;

class FeatureTogglesTest extends TestCase
{
    public function test_shop_is_hidden_and_404s_when_disabled(): void
    {
        // Off is the default.
        $this->get('/shop')->assertNotFound();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertDontSee('href="/shop"', false);
    }

    public function test_shop_is_visible_and_reachable_when_enabled(): void
    {
        $this->setFeature('shop_enabled', true);

        $this->get('/shop')->assertOk();

        $home = $this->get('/');
        $home->assertSee('href="/shop"', false);
    }

    public function test_sitemap_includes_shop_only_when_enabled(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>/shop</loc>', false);

        $this->setFeature('shop_enabled', true);

        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>/shop</loc>', false);
    }

    public function test_calendar_navigation_is_hidden_until_a_booking_exists(): void
    {
        Booking::forgetPresenceCache();

        $this->assertFalse(BookingCalendar::shouldRegisterNavigation());

        Booking::factory()->create();

        // The observer busts the cached presence flag on create.
        $this->assertTrue(BookingCalendar::shouldRegisterNavigation());
    }

    public function test_course_message_history_is_hidden_until_a_message_is_sent(): void
    {
        $course = CourseDate::factory()->create();

        $this->assertFalse(MessagesRelationManager::canViewForRecord($course, EditCourseDate::class));

        CourseMessage::factory()->create(['course_date_id' => $course->id]);

        $this->assertTrue(MessagesRelationManager::canViewForRecord($course->refresh(), EditCourseDate::class));
    }
}
