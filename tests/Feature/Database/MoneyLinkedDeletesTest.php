<?php

namespace Tests\Feature\Database;

use App\Actions\EraseCustomerData;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\DeletionBlockedException;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TandemDate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Prompt 008 Phase 2: deletes that money, a booking or a customer's history
 * hangs off are refused by the model (any Eloquent delete) AND by the database
 * (deleteQuietly() or a raw query, which skip the model), not just by the
 * admin's disabled button.
 */
class MoneyLinkedDeletesTest extends TestCase
{
    public function test_a_product_with_course_dates_is_refused_by_the_model(): void
    {
        $product = Product::factory()->create();
        $course = CourseDate::factory()->create(['product_id' => $product->id]);

        try {
            $product->delete();
            $this->fail('Deleting a product with course dates was allowed.');
        } catch (DeletionBlockedException $e) {
            $this->assertSame($product->deletionBlocker(), $e->getMessage());
        }

        $this->assertModelExists($product);
        $this->assertModelExists($course);
    }

    public function test_a_product_with_course_dates_is_refused_by_the_database_when_the_model_is_bypassed(): void
    {
        $product = Product::factory()->create();
        $course = CourseDate::factory()->create(['product_id' => $product->id]);

        $this->assertRefusedByTheDatabase(fn () => $product->deleteQuietly());
        $this->assertRefusedByTheDatabase(fn () => DB::table('products')->where('id', $product->id)->delete());

        // Before 008 this cascaded: the course date went with the product.
        $this->assertModelExists($course);
    }

    public function test_a_booking_with_a_payment_is_refused_by_the_model_and_by_the_database(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->expectRefusalByTheModel(fn () => $booking->delete());
        $this->assertRefusedByTheDatabase(fn () => $booking->deleteQuietly());
        $this->assertRefusedByTheDatabase(fn () => DB::table('bookings')->where('id', $booking->id)->delete());

        // Before 008 the payment survived with booking_id set to null.
        $this->assertSame($booking->id, $payment->fresh()->booking_id);
    }

    public function test_a_document_attached_to_a_sent_message_is_refused(): void
    {
        $document = Document::factory()->create();
        CourseMessage::factory()->create()->documents()->attach($document);

        $this->assertStringContainsString('Attached to 1 sent message', (string) $document->deletionBlocker());
        $this->expectRefusalByTheModel(fn () => $document->delete());
        $this->assertRefusedByTheDatabase(fn () => $document->deleteQuietly());
        $this->assertSame(1, $document->courseMessages()->count());
    }

    public function test_a_tandem_date_with_only_cancelled_bookings_explains_and_refuses(): void
    {
        $date = TandemDate::factory()->create();
        Booking::factory()->create(['tandem_date_id' => $date->id, 'status' => BookingStatus::Cancelled]);

        // The button must say what the database will do: a cancelled booking still points here.
        $this->assertSame('1 cancelled booking(s) still record this date, so it stays as history.', $date->deletionBlocker());
        $this->expectRefusalByTheModel(fn () => $date->delete());
        $this->assertRefusedByTheDatabase(fn () => $date->deleteQuietly());
    }

    public function test_records_nothing_hangs_off_still_delete(): void
    {
        $product = Product::factory()->create();
        $document = Document::factory()->create();
        $date = TandemDate::factory()->create();
        $booking = Booking::factory()->create();

        $product->delete();
        $document->delete();
        $date->delete();
        $booking->delete();

        $this->assertModelMissing($product);
        $this->assertModelMissing($document);
        $this->assertModelMissing($date);
        $this->assertModelMissing($booking);
    }

    public function test_erasure_still_works_on_a_customer_with_paid_bookings(): void
    {
        $customer = Customer::factory()->create(['email' => 'jess@example.com']);
        $product = Product::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id, 'product_id' => $product->id, 'email' => 'jess@example.com']);
        $payment = Payment::factory()->create(['booking_id' => $booking->id, 'status' => PaymentStatus::Paid]);

        app(EraseCustomerData::class)->handle($customer);

        $this->assertNotNull($customer->fresh()->erased_at);
        $this->assertSame($customer->id, $booking->fresh()->customer_id);
        $this->assertSame($booking->id, $payment->fresh()->booking_id);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
    }

    private function expectRefusalByTheModel(callable $delete): void
    {
        try {
            $delete();
            $this->fail('The model allowed the delete.');
        } catch (DeletionBlockedException) {
            $this->addToAssertionCount(1);
        }
    }

    private function assertRefusedByTheDatabase(callable $delete): void
    {
        try {
            $delete();
            $this->fail('The database allowed the delete.');
        } catch (QueryException $e) {
            $this->assertMatchesRegularExpression('/foreign key|FOREIGN KEY|a foreign key constraint fails/i', $e->getMessage());
        }
    }
}
