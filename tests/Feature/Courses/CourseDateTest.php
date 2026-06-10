<?php

declare(strict_types=1);

namespace Tests\Feature\Courses;

use App\Enums\BookingPaymentState;
use App\Enums\BookingStatus;
use App\Enums\CourseDateStatus;
use App\Filament\Resources\CourseDates\Pages\CreateCourseDate;
use App\Filament\Resources\CourseDates\Pages\EditCourseDate;
use App\Filament\Resources\CourseDates\RelationManagers\BookingsRelationManager;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CourseDateTest extends TestCase
{
    public function test_pricing_falls_back_to_the_product_and_respects_overrides(): void
    {
        $product = Product::factory()->aff()->create(['price_pence' => 175000, 'deposit_pence' => 30000]);

        $default = CourseDate::factory()->create(['product_id' => $product->id]);
        $this->assertSame(175000, $default->effective_price_pence);
        $this->assertSame(30000, $default->effective_deposit_pence);
        $this->assertSame('£1,750', $default->formatted_price);

        $override = CourseDate::factory()->create([
            'product_id' => $product->id,
            'price_pence' => 165000,
            'deposit_pence' => 25000,
        ]);
        $this->assertSame(165000, $override->effective_price_pence);
        $this->assertSame('£250', $override->formatted_deposit);
    }

    public function test_capacity_and_derived_full_status(): void
    {
        $course = CourseDate::factory()->create(['capacity' => 2]);

        $this->assertTrue($course->isBookable());

        Booking::factory()->count(2)->create(['course_date_id' => $course->id]);
        $course->refresh();

        $this->assertSame(0, $course->remaining_places);
        $this->assertSame(CourseDateStatus::Full, $course->display_status);
        $this->assertFalse($course->isBookable());

        // A cancellation frees the place again.
        $course->bookings()->first()->update(['status' => BookingStatus::Cancelled]);
        $this->assertSame(1, $course->refresh()->remaining_places);
        $this->assertTrue($course->isBookable());
    }

    public function test_past_and_non_open_courses_are_not_bookable(): void
    {
        $past = CourseDate::factory()->create(['starts_on' => now()->subWeek()->toDateString()]);
        $cancelled = CourseDate::factory()->create(['status' => CourseDateStatus::Cancelled]);

        $this->assertFalse($past->isBookable());
        $this->assertFalse($cancelled->isBookable());
        $this->assertFalse(CourseDate::upcomingOpen()->get()->contains($past));
        $this->assertFalse(CourseDate::upcomingOpen()->get()->contains($cancelled));
    }

    public function test_admin_can_create_a_course_date(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->aff()->create();
        $location = Location::factory()->create(['name' => 'Seville, Spain']);

        Livewire::test(CreateCourseDate::class)
            ->fillForm([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'starts_on' => now()->addMonth()->toDateString(),
                'ends_on' => now()->addMonth()->addDays(4)->toDateString(),
                'capacity' => 8,
                'status' => CourseDateStatus::Open->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('course_dates', ['location_id' => $location->id, 'capacity' => 8]);
    }

    public function test_relation_manager_shows_enrolled_customers_with_payment_state(): void
    {
        $this->actingAs(User::factory()->create());
        $course = CourseDate::factory()->create(['capacity' => 8]);

        $depositPaid = Booking::factory()->create([
            'course_date_id' => $course->id,
            'name' => 'Deposit Dan',
            'price_pence' => 175000,
        ]);
        $depositPaid->payments()->create([
            'purpose' => 'aff_deposit',
            'method' => 'stripe',
            'status' => 'paid',
            'amount_pence' => 30000,
            'paid_at' => now(),
        ]);

        Booking::factory()->create([
            'course_date_id' => $course->id,
            'name' => 'Unpaid Una',
            'price_pence' => 175000,
        ]);

        $this->assertSame(BookingPaymentState::DepositPaid, $depositPaid->refresh()->payment_state);

        Livewire::test(BookingsRelationManager::class, [
            'ownerRecord' => $course,
            'pageClass' => EditCourseDate::class,
        ])
            ->assertOk()
            ->assertSee('Deposit Dan')
            ->assertSee('Deposit paid — balance outstanding')
            ->assertSee('Unpaid Una')
            ->assertSee('£1,450');
    }
}
