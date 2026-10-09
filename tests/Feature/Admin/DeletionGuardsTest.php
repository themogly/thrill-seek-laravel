<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\CourseDates\Pages\EditCourseDate;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\TandemDates\Pages\EditTandemDate;
use App\Filament\Resources\Vouchers\Pages\EditVoucher;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use App\Models\Document;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TandemDate;
use App\Models\User;
use App\Models\Voucher;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A record that money or other records hang off can't be deleted from the
 * admin: the Delete button is disabled with the reason, and calling the action
 * directly (bypassing the screen) still deletes nothing. A record nothing
 * depends on stays deletable.
 */
class DeletionGuardsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_a_product_with_course_dates_cannot_be_deleted(): void
    {
        $product = Product::factory()->aff()->create();
        CourseDate::factory()->create(['product_id' => $product->id]);

        $this->assertRefused(EditProduct::class, $product);
    }

    public function test_a_booking_with_a_payment_cannot_be_deleted(): void
    {
        $booking = Booking::factory()->confirmed()->create();
        Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->assertRefused(EditBooking::class, $booking);
    }

    public function test_a_course_with_students_cannot_be_deleted(): void
    {
        $course = CourseDate::factory()->create();
        Booking::factory()->create(['course_date_id' => $course->id]);

        $this->assertRefused(EditCourseDate::class, $course);
    }

    public function test_a_tandem_date_with_customers_cannot_be_deleted(): void
    {
        $slot = TandemDate::factory()->create();
        Booking::factory()->confirmed()->create(['tandem_date_id' => $slot->id]);

        $this->assertRefused(EditTandemDate::class, $slot);
    }

    public function test_a_location_in_use_cannot_be_deleted(): void
    {
        $slot = TandemDate::factory()->create();

        $this->assertRefused(EditLocation::class, $slot->location);
    }

    public function test_a_bought_or_used_voucher_cannot_be_deleted(): void
    {
        $bought = Voucher::factory()->create(['source' => 'online']);
        $used = Voucher::factory()->create(['source' => 'admin', 'status' => VoucherStatus::Redeemed, 'redeemed_at' => now()]);

        $this->assertRefused(EditVoucher::class, $bought);
        $this->assertRefused(EditVoucher::class, $used);
    }

    public function test_a_document_sent_to_students_cannot_be_deleted(): void
    {
        $document = Document::factory()->create();
        CourseMessage::factory()->create()->documents()->attach($document);

        $this->assertRefused(EditDocument::class, $document);
    }

    public function test_a_tandem_date_with_only_cancelled_bookings_cannot_be_deleted(): void
    {
        $slot = TandemDate::factory()->create();
        Booking::factory()->create(['tandem_date_id' => $slot->id, 'status' => BookingStatus::Cancelled]);

        $this->assertRefused(EditTandemDate::class, $slot);
    }

    public function test_records_nothing_depends_on_can_still_be_deleted(): void
    {
        foreach ([
            EditProduct::class => Product::factory()->create(),
            EditBooking::class => Booking::factory()->create(),
            EditCourseDate::class => CourseDate::factory()->create(),
            EditTandemDate::class => TandemDate::factory()->create(),
            EditLocation::class => Location::factory()->create(),
            EditVoucher::class => Voucher::factory()->create(['source' => 'admin']),
            EditDocument::class => Document::factory()->create(),
        ] as $page => $record) {
            Livewire::test($page, ['record' => $record->getRouteKey()])
                ->assertActionEnabled(DeleteAction::class)
                ->callAction(DeleteAction::class);

            $this->assertModelMissing($record);
        }
    }

    public function test_bulk_delete_keeps_the_records_that_are_in_use(): void
    {
        $inUse = Product::factory()->aff()->create();
        CourseDate::factory()->create(['product_id' => $inUse->id]);
        $free = Product::factory()->create();

        Livewire::test(ListProducts::class)
            ->callTableBulkAction(DeleteBulkAction::class, [$inUse, $free])
            ->assertNotified('Deleted 1, kept 1');

        $this->assertModelExists($inUse);
        $this->assertModelMissing($free);
    }

    /**
     * @param  class-string  $page
     */
    private function assertRefused(string $page, Model $record): void
    {
        Livewire::test($page, ['record' => $record->getRouteKey()])
            ->assertActionDisabled(DeleteAction::class)
            ->callAction(DeleteAction::class);

        $this->assertModelExists($record);
    }
}
