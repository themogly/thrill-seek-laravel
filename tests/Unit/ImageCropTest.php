<?php

namespace Tests\Unit;

use App\Support\ImageCrop;
use Filament\Forms\Components\FileUpload;
use Tests\TestCase;

class ImageCropTest extends TestCase
{
    public function test_it_locks_a_file_upload_to_the_ratio_with_the_built_in_crop_editor(): void
    {
        $field = ImageCrop::ratio(FileUpload::make('image'), '1:1');

        // Filament's built-in editor is on, the source is auto-cropped to the
        // ratio, and the ratio is the one we asked for — no package, no hard reject.
        $this->assertTrue($field->hasImageEditor());
        $this->assertTrue($field->shouldAutomaticallyCropImagesToAspectRatio());
        $this->assertSame('1:1', $field->getImageAspectRatio());
        $this->assertNotNull($field->getAutomaticallyCropImagesAspectRatio());
    }

    public function test_it_supports_landscape_and_portrait_ratios(): void
    {
        $this->assertSame('16:9', ImageCrop::ratio(FileUpload::make('hero'), '16:9')->getImageAspectRatio());
        $this->assertSame('16:10', ImageCrop::ratio(FileUpload::make('news'), '16:10')->getImageAspectRatio());
        $this->assertSame('3:4', ImageCrop::ratio(FileUpload::make('portrait'), '3:4')->getImageAspectRatio());
    }
}
