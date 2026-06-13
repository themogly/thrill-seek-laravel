<?php

namespace App\Filament\Resources\NewsletterCampaigns;

use App\Enums\NewsletterCampaignStatus;
use App\Filament\Resources\NewsletterCampaigns\Pages\CreateNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\EditNewsletterCampaign;
use App\Filament\Resources\NewsletterCampaigns\Pages\ListNewsletterCampaigns;
use App\Filament\Resources\NewsletterCampaigns\Schemas\NewsletterCampaignForm;
use App\Models\NewsletterCampaign;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

    public static function form(Schema $schema): Schema
    {
        return NewsletterCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->description(fn (NewsletterCampaign $r): string => $r->subject)
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('recipient_count')
                    ->label('Recipients')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(NewsletterCampaignStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label(fn (NewsletterCampaign $r): string => $r->isSent() ? 'View' : 'Edit'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterCampaigns::route('/'),
            'create' => CreateNewsletterCampaign::route('/create'),
            'edit' => EditNewsletterCampaign::route('/{record}/edit'),
        ];
    }
}
