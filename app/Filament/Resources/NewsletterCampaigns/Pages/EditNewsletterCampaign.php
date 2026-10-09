<?php

namespace App\Filament\Resources\NewsletterCampaigns\Pages;

use App\Actions\SendNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EditNewsletterCampaign extends EditRecord
{
    protected static string $resource = NewsletterCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading('Newsletter preview')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn (NewsletterCampaign $record) => view('filament.newsletter-preview', [
                    'html' => $this->renderFor($record),
                ])),

            Action::make('sendTest')
                ->label('Send test to me')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->schema([
                    TextInput::make('email')
                        ->label('Send the test to')
                        ->email()
                        ->required()
                        ->default(fn (): ?string => Auth::user()?->email),
                ])
                ->action(function (array $data, NewsletterCampaign $record): void {
                    // A throwaway, non-persisted subscriber — never touches real
                    // subscribers or the send history.
                    $subscriber = new NewsletterSubscriber(['email' => $data['email']]);
                    $subscriber->id = 0;

                    // Sent now, not queued: the throwaway subscriber can't be restored
                    // by a worker, and the admin should see a failure, not "Test sent".
                    try {
                        Mail::to($data['email'])->sendNow(new NewsletterCampaignMail($record, $subscriber));
                    } catch (\Throwable $e) {
                        report($e);

                        Notification::make()->danger()->title('Test email failed')->body($e->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title('Test sent')->body('Check '.$data['email'].'.')->send();
                }),

            Action::make('send')
                ->label('Send to subscribers')
                ->icon('heroicon-o-rocket-launch')
                ->color('primary')
                ->visible(fn (NewsletterCampaign $record): bool => ! $record->isSent())
                ->requiresConfirmation()
                ->modalHeading('Send this newsletter?')
                ->modalDescription(fn (): string => 'It will go to '.NewsletterSubscriber::confirmed()->count().' confirmed subscriber(s). This can\'t be undone.')
                ->modalSubmitActionLabel('Send now')
                ->action(function (NewsletterCampaign $record): void {
                    // Save any unsaved edits first, then send.
                    $this->save(shouldRedirect: false);

                    $campaign = app(SendNewsletterCampaign::class)->handle($record->refresh());

                    Notification::make()
                        ->success()
                        ->title('Newsletter queued')
                        ->body("Sending to {$campaign->recipient_count} confirmed subscriber(s).")
                        ->send();

                    $this->redirect(NewsletterCampaignResource::getUrl('index'));
                }),
        ];
    }

    /** The server-side refusal behind the read-only form: a sent newsletter never saves. */
    protected function beforeSave(): void
    {
        /** @var NewsletterCampaign $record */
        $record = $this->getRecord();

        if ($record->isSent()) {
            Notification::make()->warning()->title('This newsletter has been sent and can’t be changed')->send();

            $this->halt();
        }
    }

    /** No Save button on a sent newsletter — it's read-only history. */
    protected function getFormActions(): array
    {
        /** @var NewsletterCampaign $record */
        $record = $this->getRecord();

        return $record->isSent() ? [$this->getCancelFormAction()] : parent::getFormActions();
    }

    private function renderFor(NewsletterCampaign $record): string
    {
        $subscriber = new NewsletterSubscriber(['email' => 'preview@example.com']);
        $subscriber->id = 0;

        return (new NewsletterCampaignMail($record, $subscriber))->render();
    }
}
