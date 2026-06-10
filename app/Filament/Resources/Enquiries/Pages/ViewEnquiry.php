<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Pages;

use App\Actions\SendEnquiryReply;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewEnquiry extends ViewRecord
{
    protected static string $resource = EnquiryResource::class;

    public function getRecord(): Enquiry
    {
        $record = parent::getRecord();

        if (! $record instanceof Enquiry) {
            throw new \LogicException('Expected an Enquiry record.');
        }

        return $record;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->markRead();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    Textarea::make('body')
                        ->label('Your reply')
                        ->helperText('Sent to the customer by email and saved on this thread.')
                        ->rows(8)
                        ->required(),
                ])
                ->action(function (array $data, SendEnquiryReply $sendReply): void {
                    $enquiry = $this->getRecord();
                    /** @var User $user */
                    $user = auth()->user();

                    $sendReply->handle($enquiry, $data['body'], $user);

                    Notification::make()
                        ->success()
                        ->title('Reply sent')
                        ->body("Emailed to {$enquiry->email}.")
                        ->send();
                }),
            Action::make('changeStatus')
                ->label('Change status')
                ->icon('heroicon-o-tag')
                ->color('gray')
                ->form([
                    Select::make('status')
                        ->label('Status')
                        ->options(EnquiryStatus::class)
                        ->default(fn (): string => $this->getRecord()->status->value)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->update(['status' => $data['status']]);

                    Notification::make()->success()->title('Status updated')->send();
                }),
        ];
    }
}
