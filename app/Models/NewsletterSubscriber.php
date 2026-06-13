<?php

namespace App\Models;

use App\Enums\NewsletterStatus;
use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property NewsletterStatus $status
 * @property Carbon|null $consented_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $unsubscribed_at
 * @property Carbon|null $created_at
 */
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'status',
        'source',
        'consented_at',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsletterStatus::class,
            'consented_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public static function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Only confirmed, never-unsubscribed addresses may be sent newsletters.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', NewsletterStatus::Confirmed);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSuppressed(Builder $query): Builder
    {
        return $query->where('status', NewsletterStatus::Unsubscribed);
    }

    public function isConfirmed(): bool
    {
        return $this->status === NewsletterStatus::Confirmed;
    }

    public function markConfirmed(): void
    {
        $this->forceFill([
            'status' => NewsletterStatus::Confirmed,
            'confirmed_at' => $this->confirmed_at ?? Carbon::now(),
            'unsubscribed_at' => null,
        ])->save();
    }

    public function markUnsubscribed(): void
    {
        $this->forceFill([
            'status' => NewsletterStatus::Unsubscribed,
            'unsubscribed_at' => Carbon::now(),
        ])->save();
    }
}
