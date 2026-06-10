<?php

namespace Tests\Feature\Courses;

use App\Actions\SendCourseMessage;
use App\Enums\BookingStatus;
use App\Enums\CourseDateStatus;
use App\Filament\Resources\CourseDates\Pages\EditCourseDate;
use App\Jobs\SendCourseMessageToRecipient;
use App\Mail\CourseMessageMail;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\CourseMessage;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class CourseCommunicationsTest extends TestCase
{
    public function test_messaging_a_course_queues_one_job_per_student_and_records_history(): void
    {
        Queue::fake();
        $course = CourseDate::factory()->create(['capacity' => 10]);

        $confirmed = Booking::factory()->create(['course_date_id' => $course->id, 'status' => BookingStatus::Confirmed]);
        $depositPaid = Booking::factory()->create(['course_date_id' => $course->id, 'status' => BookingStatus::PendingDate]);
        Booking::factory()->create(['course_date_id' => $course->id, 'status' => BookingStatus::Cancelled, 'email' => 'gone@example.com']);
        Booking::factory()->create(['course_date_id' => $course->id, 'status' => BookingStatus::PendingPayment, 'email' => 'hold@example.com']);

        $sender = User::factory()->create();
        $documents = Document::factory()->count(2)->create(['size_bytes' => 1_000_000]);

        $message = app(SendCourseMessage::class)->handle(
            $course,
            'Kit list for Spain',
            "Hi everyone,\n\nAttached is the kit list.",
            $documents,
            $sender,
        );

        Queue::assertPushed(SendCourseMessageToRecipient::class, 2);
        Queue::assertPushed(SendCourseMessageToRecipient::class, fn ($job) => $job->recipientEmail === $confirmed->email);
        Queue::assertPushed(SendCourseMessageToRecipient::class, fn ($job) => $job->recipientEmail === $depositPaid->email);
        Queue::assertNotPushed(SendCourseMessageToRecipient::class, fn ($job) => in_array($job->recipientEmail, ['gone@example.com', 'hold@example.com'], true));

        $this->assertSame(2, $message->recipientCount());
        $this->assertSame(2, $message->documents()->count());
        $this->assertSame('Kit list for Spain', $course->messages()->sole()->subject);
    }

    public function test_the_recipient_job_sends_the_mail_with_attachments(): void
    {
        Mail::fake();
        Storage::fake('local');
        Storage::disk('local')->put('documents/kit-list.pdf', '%PDF-1.4 fake');

        $message = CourseMessage::factory()->create(['subject' => 'Kit list']);
        $document = Document::factory()->create([
            'file_path' => 'documents/kit-list.pdf',
            'original_filename' => 'kit-list.pdf',
        ]);
        $message->documents()->attach($document);

        (new SendCourseMessageToRecipient($message, 'student@example.com', 'Student'))->handle();

        Mail::assertSent(CourseMessageMail::class, function (CourseMessageMail $mail) {
            return $mail->hasTo('student@example.com')
                && count($mail->attachments()) === 1;
        });
    }

    public function test_oversized_attachment_batches_are_rejected(): void
    {
        $course = CourseDate::factory()->create();
        Booking::factory()->create(['course_date_id' => $course->id]);
        $documents = Document::factory()->count(2)->create(['size_bytes' => 8 * 1024 * 1024]);

        $this->expectException(InvalidArgumentException::class);

        app(SendCourseMessage::class)->handle($course, 'Too big', 'Body', $documents, User::factory()->create());
    }

    public function test_admin_can_message_students_from_the_course_page(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $course = CourseDate::factory()->create();
        Booking::factory()->count(2)->create(['course_date_id' => $course->id]);

        Livewire::test(EditCourseDate::class, ['record' => $course->getRouteKey()])
            ->callAction('messageStudents', [
                'subject' => 'Welcome aboard',
                'body' => 'See you at ground school.',
            ])
            ->assertHasNoActionErrors();

        Queue::assertPushed(SendCourseMessageToRecipient::class, 2);
        $this->assertSame(1, $course->messages()->count());
    }

    public function test_new_courses_get_a_default_reminder(): void
    {
        $course = CourseDate::factory()->create();

        $this->assertSame(1, $course->reminders()->count());
        $this->assertSame(7, $course->reminders()->sole()->days_before);
    }

    public function test_due_reminders_send_once_and_never_double_send(): void
    {
        Queue::fake();
        $course = CourseDate::factory()->create(['start_date' => now()->addDays(5)->toDateString()]);
        Booking::factory()->count(3)->create(['course_date_id' => $course->id]);

        // Default reminder is 7 days before — already due at 5 days out.
        $this->artisan('courses:send-reminders')
            ->expectsOutputToContain('Sent 1 course reminder(s).')
            ->assertSuccessful();

        Queue::assertPushed(SendCourseMessageToRecipient::class, 3);
        $this->assertNotNull($course->reminders()->sole()->refresh()->sent_at);
        $this->assertSame('reminder', $course->messages()->sole()->source);

        // A second scheduler tick must be a no-op.
        $this->artisan('courses:send-reminders')
            ->expectsOutputToContain('Sent 0 course reminder(s).')
            ->assertSuccessful();

        Queue::assertPushed(SendCourseMessageToRecipient::class, 3);
        $this->assertSame(1, $course->messages()->count());
    }

    public function test_reminders_for_far_future_or_cancelled_courses_do_not_send(): void
    {
        Queue::fake();
        CourseDate::factory()->create(['start_date' => now()->addMonths(3)->toDateString()]);
        $cancelled = CourseDate::factory()->create([
            'start_date' => now()->addDays(3)->toDateString(),
            'status' => CourseDateStatus::Cancelled,
        ]);
        Booking::factory()->create(['course_date_id' => $cancelled->id]);

        $this->artisan('courses:send-reminders')
            ->expectsOutputToContain('Sent 0 course reminder(s).');

        Queue::assertNothingPushed();
    }
}
