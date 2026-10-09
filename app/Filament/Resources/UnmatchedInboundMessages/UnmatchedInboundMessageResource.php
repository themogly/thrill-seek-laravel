<?php

namespace App\Filament\Resources\UnmatchedInboundMessages;

use App\Filament\Resources\UnmatchedInboundMessages\Pages\ListUnmatchedInboundMessages;
use App\Models\UnmatchedInboundMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Inbound email that couldn't be routed to an enquiry — surfaced for manual review
 * rather than silently dropped. Read-only: the owner reads it, acts in their normal
 * inbox if needed, and marks it reviewed.
 */
class UnmatchedInboundMessageResource extends Resource
{
    protected static ?string $model = UnmatchedInboundMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'unmatched message';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = UnmatchedInboundMessage::unreviewed()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('from_email')->label('From')->searchable()->copyable(),
                TextColumn::make('subject')->label('Subject')->limit(50)->placeholder('—'),
                TextColumn::make('reason')->label('Why')->badge()->color('warning'),
                TextColumn::make('received_at')->label('Received')->since()->sortable(),
                TextColumn::make('reviewed_at')->label('Reviewed')->since()->placeholder('Not yet')->sortable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Read')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (UnmatchedInboundMessage $r): string => $r->subject ?? 'Message')
                    ->modalContent(fn (UnmatchedInboundMessage $r) => view('filament.unmatched-message', ['message' => $r]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('markReviewed')
                    ->label('Mark reviewed')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (UnmatchedInboundMessage $r): bool => $r->reviewed_at === null)
                    ->action(fn (UnmatchedInboundMessage $r) => $r->update(['reviewed_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnmatchedInboundMessages::route('/'),
        ];
    }
}
