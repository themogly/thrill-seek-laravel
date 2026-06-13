<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Enums\NewsletterStatus;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsletterSubscriber extends CreateRecord
{
    protected static string $resource = NewsletterSubscriberResource::class;

    /**
     * An admin-added subscriber is someone who asked to join in person, so we
     * record consent now and mark them confirmed — they skip double opt-in but
     * the consent timestamp is still stored for compliance.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email'] = NewsletterSubscriber::normaliseEmail((string) $data['email']);
        $data['status'] = NewsletterStatus::Confirmed;
        $data['source'] = 'admin';
        $data['consented_at'] = now();
        $data['confirmed_at'] = now();

        return $data;
    }
}
