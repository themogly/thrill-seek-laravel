<?php

namespace App\Filament\Resources\NewsletterCampaigns;

use App\Filament\Resources\NewsletterCampaigns\Pages\ListNewsletterCampaigns;
use App\Filament\Resources\NewsletterCampaigns\Pages\ViewNewsletterCampaign;
use App\Models\NewsletterCampaign;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterCampaignResource extends Resource
{
    protected static ?string $model = NewsletterCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'newsletter';

    protected static ?string $pluralModelLabel = 'newsletters';

    public static function canCreate(): bool
    {
        // Newsletters are composed and sent in one go via the list header action.
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('subject'),
            TextEntry::make('sent_at')->label('Sent')->dateTime(),
            TextEntry::make('recipient_count')->label('Recipients'),
            TextEntry::make('user.name')->label('Sent by')->placeholder('—'),
            TextEntry::make('body')->label('Message')->html()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('recipient_count')
                    ->label('Recipients')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Sent by')
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterCampaigns::route('/'),
            'view' => ViewNewsletterCampaign::route('/{record}'),
        ];
    }
}
