<?php

namespace App\Models;

use App\Enums\NewsletterCampaignStatus;
use Database\Factories\NewsletterCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A block-based newsletter. Composed as ordered content blocks, previewed and
 * test-sent, then broadcast to confirmed subscribers and kept as send history
 * (recipient count, sent-at, and the rendered HTML frozen at send time).
 *
 * @property NewsletterCampaignStatus $status
 * @property array<int, array{type: string, data: array<string, mixed>}>|null $blocks
 * @property Carbon|null $sent_at
 * @property Carbon|null $scheduled_at
 */
class NewsletterCampaign extends Model
{
    /** @use HasFactory<NewsletterCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'preheader',
        'status',
        'body',
        'blocks',
        'rendered_html',
        'recipient_count',
        'sent_at',
        'scheduled_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsletterCampaignStatus::class,
            'blocks' => 'array',
            'sent_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'recipient_count' => 'integer',
        ];
    }

    public function isSent(): bool
    {
        return $this->status === NewsletterCampaignStatus::Sent;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<NewsletterCampaignRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class);
    }
}
