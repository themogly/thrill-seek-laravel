<?php

namespace App\Filament\Resources\NewsletterCampaigns\Pages;

use App\Actions\SendNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListNewsletterCampaigns extends ListRecords
{
    protected static string $resource = NewsletterCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compose')
                ->label('Compose newsletter')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->modalSubmitActionLabel('Send to confirmed subscribers')
                ->schema([
                    TextInput::make('subject')
                        ->label('Subject')
                        ->required()
                        ->maxLength(255),
                    RichEditor::make('body')
                        ->label('Message')
                        ->helperText(fn (): string => 'Goes to '.NewsletterSubscriber::confirmed()->count().' confirmed subscriber(s) in the G-Force email theme. Unsubscribed and unconfirmed addresses are skipped.')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $campaign = app(SendNewsletterCampaign::class)->handle(
                        $data['subject'],
                        $data['body'],
                        Auth::user(),
                    );

                    Notification::make()
                        ->success()
                        ->title('Newsletter queued')
                        ->body("Sending to {$campaign->recipient_count} confirmed subscriber(s).")
                        ->send();
                }),
        ];
    }
}
