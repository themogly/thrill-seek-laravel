<?php

namespace Tests\Feature\Admin;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\GalleryImages\Pages\CreateGalleryImage;
use App\Filament\Resources\News\Pages\EditNews;
use App\Models\Booking;
use App\Models\CourseDate;
use App\Models\Enquiry;
use App\Models\GalleryImage;
use App\Models\NewsArticle;
use App\Models\TandemDate;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prompt 025 (the admin audit's A-4 batch): a past choice no longer blocks
 * Save, image uploads refuse unsafe types, the inbox can be re-sorted, and the
 * navigation has one order by intent.
 */
class AdminTidyUpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_a_booking_whose_slot_has_passed_shows_it_and_still_saves(): void
    {
        $past = TandemDate::factory()->create(['starts_at' => now()->subWeek()->setTime(9, 0)]);
        $booking = Booking::factory()->confirmed()->create(['tandem_date_id' => $past->id, 'scheduled_at' => $past->starts_at]);

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->assertFormFieldExists('tandem_date_id', function (Select $field) use ($past): bool {
                $this->assertStringContainsString($past->starts_at->format('D j M Y, H:i'), (string) $field->getOptionLabel());
                $this->assertStringEndsWith('— no longer bookable', (string) $field->getOptionLabel());

                return true;
            })
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($past->id, $booking->fresh()->tandem_date_id);
    }

    public function test_a_news_article_linked_to_a_past_course_still_saves(): void
    {
        $course = CourseDate::factory()->create(['start_date' => now()->subMonth(), 'end_date' => now()->subMonth()->addDays(4)]);
        $article = NewsArticle::factory()->create(['course_date_id' => $course->id]);

        Livewire::test(EditNews::class, ['record' => $article->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($course->id, $article->fresh()->course_date_id);
    }

    public function test_image_fields_refuse_svg_and_gif_and_take_jpeg_png_webp(): void
    {
        Storage::fake('public');

        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><script>alert(1)</script></svg>');

        // A cropped field (ImageCrop) and a free-form one (Testimonial avatar has its own crop; gallery is 1:1).
        foreach (['svg' => $svg, 'gif' => UploadedFile::fake()->image('anim.gif', 800, 800)] as $type => $file) {
            Livewire::test(CreateGalleryImage::class)
                ->set('data.alt_text', 'x')
                ->set('data.image', $file)
                ->call('create')
                ->assertHasErrors(['data.image']);
        }

        foreach (['jpg', 'png', 'webp'] as $extension) {
            Livewire::test(CreateGalleryImage::class)
                ->set('data.alt_text', 'x')
                ->set('data.image', UploadedFile::fake()->image("photo.{$extension}", 800, 800))
                ->call('create')
                ->assertHasNoErrors(['data.image']);
        }

        $this->assertSame(3, GalleryImage::count());
    }

    public function test_an_image_over_12_mb_is_refused_with_a_plain_message(): void
    {
        Storage::fake('public');

        Livewire::test(CreateGalleryImage::class)
            ->set('data.alt_text', 'x')
            ->set('data.image', UploadedFile::fake()->image('huge.jpg', 800, 800)->size(13 * 1024))
            ->call('create')
            ->assertHasErrors(['data.image']);
    }

    public function test_choosing_a_column_sort_overrides_unread_first(): void
    {
        $oldUnread = Enquiry::factory()->create(['read_at' => null, 'status' => EnquiryStatus::New, 'created_at' => now()->subDays(2), 'last_customer_message_at' => null]);
        $newestRead = Enquiry::factory()->create(['read_at' => now(), 'status' => EnquiryStatus::New, 'created_at' => now()->subHour(), 'last_customer_message_at' => null]);
        $oldestRead = Enquiry::factory()->create(['read_at' => now(), 'status' => EnquiryStatus::New, 'created_at' => now()->subDays(5), 'last_customer_message_at' => null]);

        // Default: unread first, then most recent.
        Livewire::test(ListEnquiries::class)
            ->assertCanSeeTableRecords([$oldUnread, $newestRead, $oldestRead], inOrder: true);

        // A chosen sort takes over: oldest activity first, read or not.
        Livewire::test(ListEnquiries::class)
            ->sortTable('last_activity', 'asc')
            ->assertCanSeeTableRecords([$oldestRead, $oldUnread, $newestRead], inOrder: true);
    }

    public function test_navigation_sorts_are_unique_within_each_group_in_todays_order(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $groups = [];
        foreach (Filament::getNavigation() as $group) {
            $groups[(string) ($group->getLabel() ?? '')] = collect($group->getItems())->map(fn ($item): array => [$item->getSort(), (string) $item->getLabel()])->all();
        }

        foreach ($groups as $label => $items) {
            $sorts = array_column($items, 0);
            $this->assertSame(count($sorts), count(array_unique($sorts)), "Two items share a sort in \"{$label}\".");
        }

        $this->assertSame(
            ['Enquiries', 'Bookings', 'Unmatched Messages', 'AFF Courses', 'Tandem Dates', 'Products', 'Vouchers', 'Customers', 'Email Templates', 'Newsletter Subscribers', 'Documents', 'Newsletters', 'Locations'],
            array_column($groups['Bookings & sales'], 1),
        );
        $this->assertSame(
            ['General', 'Home page', 'Tandem page', 'AFF page', 'Coached skills page', 'Other pages', 'Before-your-jump info', 'Instructors', 'Disciplines', 'Testimonials', 'FAQs', 'Hall Of Fame', 'Gallery', 'Shop Items'],
            array_column($groups['Site content'], 1),
        );
    }
}
