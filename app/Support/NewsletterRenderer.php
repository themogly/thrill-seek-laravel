<?php

namespace App\Support;

use App\Models\NewsletterCampaign;
use Illuminate\Support\Facades\View;

/**
 * Turns a campaign's ordered blocks into email-safe HTML — each block is a
 * self-contained, inline-styled partial (tables, web-safe fonts, absolute
 * image URLs). Dynamic blocks (latest news, featured course) resolve to static
 * HTML here, so once a send stores the result it is frozen. Wrapped by the
 * gforce Markdown-Mail shell (header/footer + CSS inlining) in the mailable.
 */
class NewsletterRenderer
{
    /** @var list<string> */
    public const BLOCK_TYPES = [
        'heading',
        'paragraph',
        'image',
        'button',
        'divider',
        'two_column',
        'latest_news',
        'featured_course',
    ];

    public function renderBody(NewsletterCampaign $campaign): string
    {
        return collect($campaign->blocks ?? [])
            ->filter(fn (array $block): bool => in_array($block['type'] ?? '', self::BLOCK_TYPES, true)
                && View::exists('mail.blocks.'.$block['type']))
            ->map(fn (array $block): string => View::make('mail.blocks.'.$block['type'], [
                'data' => $block['data'] ?? [],
                'campaign' => $campaign,
            ])->render())
            ->implode("\n");
    }

    /** A readable plain-text alternative for deliverability. */
    public function plainText(string $html): string
    {
        // Surface link targets, then strip tags and collapse whitespace.
        $withLinks = preg_replace('/<a [^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is', '$2 ($1)', $html) ?? $html;
        $text = html_entity_decode(strip_tags($withLinks), ENT_QUOTES);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", (string) preg_replace('/[ \t]+/', ' ', $text)));
    }
}
