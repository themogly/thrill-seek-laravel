<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Prompt 025 guard: every admin image upload is built by AdminImages::upload()
 * (JPEG/PNG/WebP, 12 MB), and nothing calls Filament's ->image() afterwards,
 * because image() resets the accepted types to `image/*` and lets SVG back onto
 * the public disk. Documents keep their own field (PDF/Word types).
 */
class ImageUploadsUseTheFactoryTest extends TestCase
{
    /** file => why it may build its own FileUpload */
    private const OWN_UPLOAD = [
        'app/Filament/Resources/Documents/DocumentResource.php' => 'Course documents: PDF/Word/images with their own types and size (Document::ALLOWED_MIME_TYPES).',
    ];

    public function test_uploads_go_through_the_factory_and_nothing_resets_the_types(): void
    {
        $this->assertSame([], $this->violations($this->sources()));
    }

    public function test_the_guard_catches_a_planted_upload_and_a_planted_reset(): void
    {
        $planted = [
            'app/Filament/Planted.php' => "FileUpload::make('photo')->disk('public')",
            'app/Filament/PlantedReset.php' => "AdminImages::upload('photo')->image()->disk('public')",
        ];

        $this->assertSame([
            'app/Filament/Planted.php: FileUpload::make( — use AdminImages::upload()',
            'app/Filament/PlantedReset.php: ->image() resets the accepted types',
        ], $this->violations($planted));
    }

    /**
     * @param  array<string, string>  $sources  relative path => contents
     * @return list<string>
     */
    private function violations(array $sources): array
    {
        $violations = [];

        foreach ($sources as $file => $code) {
            if (str_contains($code, 'FileUpload::make(') && ! array_key_exists($file, self::OWN_UPLOAD) && $file !== 'app/Support/AdminImages.php') {
                $violations[] = "{$file}: FileUpload::make( — use AdminImages::upload()";
            }

            if (str_contains($code, '->image()') && $file !== 'app/Support/AdminImages.php') {
                $violations[] = "{$file}: ->image() resets the accepted types";
            }
        }

        return $violations;
    }

    /** @return array<string, string> */
    private function sources(): array
    {
        $sources = [];

        foreach (SourceFiles::under('app', '.php') as $file) {
            $sources[substr($file, strlen(SourceFiles::root()) + 1)] = (string) file_get_contents($file);
        }

        return $sources;
    }
}
