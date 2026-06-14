<?php

namespace App\Models;

use Database\Factories\UnmatchedInboundMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Inbound mail that couldn't be routed to an enquiry (unknown/missing token, or no
 * enquiry for the sender) — kept for manual review rather than dropped.
 *
 * @property Carbon|null $received_at
 * @property Carbon|null $reviewed_at
 */
class UnmatchedInboundMessage extends Model
{
    /** @use HasFactory<UnmatchedInboundMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'from_email',
        'to_email',
        'subject',
        'body',
        'reason',
        'received_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnreviewed(Builder $query): Builder
    {
        return $query->whereNull('reviewed_at');
    }
}
