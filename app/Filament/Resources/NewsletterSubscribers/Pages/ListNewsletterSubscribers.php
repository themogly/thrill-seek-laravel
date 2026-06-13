<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add subscriber')
                ->icon('heroicon-o-plus'),
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Email', 'Name', 'Status', 'Source', 'Consented at', 'Confirmed at', 'Unsubscribed at', 'Subscribed at']);

            NewsletterSubscriber::query()->orderBy('created_at')->chunk(500, function ($subscribers) use ($handle): void {
                foreach ($subscribers as $subscriber) {
                    fputcsv($handle, [
                        $subscriber->email,
                        $subscriber->name,
                        $subscriber->status->getLabel(),
                        $subscriber->source,
                        $subscriber->consented_at?->toDateTimeString(),
                        $subscriber->confirmed_at?->toDateTimeString(),
                        $subscriber->unsubscribed_at?->toDateTimeString(),
                        $subscriber->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
