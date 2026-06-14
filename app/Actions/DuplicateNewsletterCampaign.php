<?php

namespace App\Actions;

use App\Enums\NewsletterCampaignStatus;
use App\Models\NewsletterCampaign;

/**
 * Copy a newsletter as a fresh draft the owner can tweak and resend. A genuine
 * deep copy: blocks are a JSON value array, so the copy's content is independent
 * of the original. Send state is reset (draft, no history, nothing scheduled, no
 * frozen HTML) so duplicating a *sent* newsletter can never look sent or resend.
 * Recipient rows are a relation and are deliberately NOT copied.
 *
 * Image safety: blocks carry image PATHS, not files; the copy references the same
 * stored file. Filament's Builder never deletes files referenced in nested JSON
 * when a block is edited or removed, and replacing an image uploads a new file —
 * so neither campaign can pull an image out from under the other.
 */
class DuplicateNewsletterCampaign
{
    public function handle(NewsletterCampaign $original, ?int $userId = null): NewsletterCampaign
    {
        return NewsletterCampaign::create([
            'name' => 'Copy of '.$original->name,
            'subject' => $original->subject,
            'preheader' => $original->preheader,
            'body' => $original->body,
            'blocks' => $original->blocks,
            'status' => NewsletterCampaignStatus::Draft,
            'rendered_html' => null,
            'recipient_count' => 0,
            'sent_at' => null,
            'scheduled_at' => null,
            'user_id' => $userId,
        ]);
    }
}
