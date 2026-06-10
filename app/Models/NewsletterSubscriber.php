<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    protected $fillable = [
        'email',
    ];

    public static function subscribe(string $email): self
    {
        return self::firstOrCreate(['email' => mb_strtolower(trim($email))]);
    }
}
