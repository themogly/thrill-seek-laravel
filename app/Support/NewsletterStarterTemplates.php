<?php

namespace App\Support;

/**
 * Pre-built starting points for a new newsletter. Each template is just an ordered
 * set of the SAME blocks the builder produces (with editable placeholder copy) — no
 * separate rendering path, so they stay email-safe automatically.
 *
 * To add a template: append one entry below with a `label`, a one-line `description`
 * and a `blocks` array (each block `['type' => ..., 'data' => [...]]`, types from
 * NewsletterRenderer::BLOCK_TYPES). It appears in the create-screen picker
 * automatically. Keep copy on-brand and obviously a placeholder the owner replaces.
 */
class NewsletterStarterTemplates
{
    /**
     * @return array<string, array{label: string, description: string, blocks: list<array{type: string, data: array<string, mixed>}>}>
     */
    public static function all(): array
    {
        return [
            'blank' => [
                'label' => 'Blank',
                'description' => 'Start from scratch.',
                'blocks' => [],
            ],
            'new_dates' => [
                'label' => 'New dates announcement',
                'description' => 'Logo, intro, the next open course and a booking button.',
                'blocks' => [
                    ['type' => 'logo', 'data' => []],
                    ['type' => 'heading', 'data' => ['text' => 'New dates just landed', 'level' => 'h1']],
                    ['type' => 'paragraph', 'data' => ['text' => "<p>We've just opened a fresh batch of jump dates. Spaces always go quickly — grab yours before they're gone.</p>"]],
                    ['type' => 'featured_course', 'data' => []],
                    ['type' => 'button', 'data' => ['label' => 'See all dates', 'url' => '/tandem']],
                ],
            ],
            'seasonal_update' => [
                'label' => 'Seasonal / general update',
                'description' => 'Logo, hero image, a heading, text, an image + text row and a button.',
                'blocks' => [
                    ['type' => 'logo', 'data' => []],
                    ['type' => 'image', 'data' => ['image' => '/images/hero-skydive.jpg', 'caption' => '', 'link' => '']],
                    ['type' => 'heading', 'data' => ['text' => 'What\'s happening at the dropzone', 'level' => 'h1']],
                    ['type' => 'paragraph', 'data' => ['text' => '<p>A quick update on what we\'ve been up to and what\'s coming next. Read on for the highlights.</p>']],
                    ['type' => 'two_column', 'data' => ['image' => '/images/aff.jpg', 'image_side' => 'left', 'heading' => 'Go further', 'text' => 'Thinking about your licence? Our AFF course takes you from first jump to qualified.', 'button_label' => 'Learn about AFF', 'button_url' => '/aff']],
                    ['type' => 'button', 'data' => ['label' => 'Visit the site', 'url' => '/']],
                ],
            ],
            'single_announcement' => [
                'label' => 'Single announcement',
                'description' => 'Logo, one big image, a heading, a short message and one prominent button.',
                'blocks' => [
                    ['type' => 'logo', 'data' => []],
                    ['type' => 'image', 'data' => ['image' => '/images/hero-skydive.jpg', 'caption' => '', 'link' => '']],
                    ['type' => 'heading', 'data' => ['text' => 'We\'ve got something to tell you', 'level' => 'h1']],
                    ['type' => 'paragraph', 'data' => ['text' => '<p>Replace this with your announcement — keep it short and to the point, then send people to the right place with the button below.</p>']],
                    ['type' => 'button', 'data' => ['label' => 'Find out more', 'url' => '/']],
                ],
            ],
        ];
    }

    /** @return array<string, string> key => label, for a Filament select. */
    public static function options(): array
    {
        return array_map(fn (array $t): string => $t['label'], self::all());
    }

    /** @return list<array{type: string, data: array<string, mixed>}> */
    public static function blocks(string $key): array
    {
        return self::all()[$key]['blocks'] ?? [];
    }
}
