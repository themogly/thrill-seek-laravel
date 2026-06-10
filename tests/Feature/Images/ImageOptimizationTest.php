<?php

namespace Tests\Feature\Images;

use App\Jobs\OptimizeUploadedImage;
use App\Models\GalleryImage;
use App\Models\Instructor;
use App\Settings\HomePageSettings;
use App\Support\ImageOptimization;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    public function test_the_job_resizes_converts_to_webp_and_rewrites_references(): void
    {
        Storage::fake('public');
        $this->putTestImage('gallery/big-photo.jpg', 3000, 2000);
        $image = GalleryImage::factory()->create(['image' => 'gallery/big-photo.jpg']);

        (new OptimizeUploadedImage('gallery/big-photo.jpg'))->handle();

        Storage::disk('public')->assertExists('gallery/big-photo.webp');
        Storage::disk('public')->assertExists('gallery/big-photo.jpg'); // original kept as fallback

        $optimised = (new ImageManager(new Driver))->decodeBinary((string) Storage::disk('public')->get('gallery/big-photo.webp'));
        $this->assertLessThanOrEqual(ImageOptimization::MAX_DIMENSIONS['gallery'], $optimised->width());
        $this->assertLessThanOrEqual(ImageOptimization::MAX_DIMENSIONS['gallery'], $optimised->height());

        $this->assertSame('gallery/big-photo.webp', $image->refresh()->image);
    }

    public function test_the_job_rewrites_settings_references(): void
    {
        Storage::fake('public');
        $this->putTestImage('pages/new-hero.jpg', 2400, 1600);

        $settings = app(HomePageSettings::class);
        $settings->hero_image = 'pages/new-hero.jpg';
        $settings->save();

        (new OptimizeUploadedImage('pages/new-hero.jpg'))->handle();

        $this->assertSame('pages/new-hero.webp', app(HomePageSettings::class)->refresh()->hero_image);
    }

    public function test_bundled_and_already_optimised_paths_are_skipped(): void
    {
        $this->assertFalse(ImageOptimization::isOptimisablePath('/images/tandem.jpg'));
        $this->assertFalse(ImageOptimization::isOptimisablePath('gallery/photo.webp'));
        $this->assertFalse(ImageOptimization::isOptimisablePath(null));
        $this->assertTrue(ImageOptimization::isOptimisablePath('gallery/photo.jpg'));
    }

    public function test_saving_a_model_with_a_new_upload_queues_the_job(): void
    {
        Queue::fake();

        $instructor = Instructor::factory()->create(['photo' => null]);
        $instructor->update(['photo' => 'instructors/joby.jpg']);

        Queue::assertPushed(OptimizeUploadedImage::class, fn (OptimizeUploadedImage $job) => $job->path === 'instructors/joby.jpg');
    }

    public function test_rewriting_references_does_not_retrigger_optimisation(): void
    {
        Storage::fake('public');
        $this->putTestImage('instructors/joby.jpg', 800, 800);
        $instructor = Instructor::factory()->create(['photo' => 'instructors/joby.jpg']);

        Queue::fake();
        (new OptimizeUploadedImage('instructors/joby.jpg'))->handle();

        $this->assertSame('instructors/joby.webp', $instructor->refresh()->photo);
        Queue::assertNothingPushed();
    }

    public function test_backfill_command_queues_unoptimised_images_once(): void
    {
        Queue::fake();
        Instructor::factory()->create(['photo' => 'instructors/a.jpg']);
        GalleryImage::factory()->create(['image' => 'gallery/b.png']);
        GalleryImage::factory()->create(['image' => '/images/tandem.jpg']); // bundled — skipped
        GalleryImage::factory()->create(['image' => 'gallery/c.webp']);     // done — skipped

        $this->artisan('images:optimize')
            ->expectsOutputToContain('Queued optimisation for 2 image(s).')
            ->assertSuccessful();

        Queue::assertPushed(OptimizeUploadedImage::class, 2);
    }

    private function putTestImage(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, (int) imagecolorallocate($image, 200, 60, 20));

        ob_start();
        imagejpeg($image, null, 90);
        $contents = (string) ob_get_clean();

        Storage::disk('public')->put($path, $contents);
    }
}
