<?php

namespace App\Filament\Resources\NewsletterCampaigns\Pages;

use App\Enums\NewsletterCampaignStatus;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNewsletterCampaign extends CreateRecord
{
    protected static string $resource = NewsletterCampaignResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = NewsletterCampaignStatus::Draft;
        $data['user_id'] = Auth::id();

        return $data;
    }
}
