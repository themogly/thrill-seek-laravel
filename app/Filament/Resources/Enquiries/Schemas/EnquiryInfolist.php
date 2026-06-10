<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Models\Enquiry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EnquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->columns(3)
                    ->components([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label('Phone')
                            ->placeholder('—'),
                        TextEntry::make('product.name')
                            ->label('About')
                            ->placeholder('General enquiry'),
                        TextEntry::make('preferred_date')
                            ->label('Preferred date')
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ]),
                Section::make('Details from the booking form')
                    ->visible(fn (Enquiry $record): bool => filled($record->context))
                    ->components([
                        KeyValueEntry::make('context')
                            ->hiddenLabel()
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->getStateUsing(fn (Enquiry $record): array => collect($record->context ?? [])
                                ->mapWithKeys(fn ($value, $key) => [Str::headline((string) $key) => $value])
                                ->all()),
                    ]),
                Section::make('Conversation')
                    ->components([
                        RepeatableEntry::make('messages')
                            ->hiddenLabel()
                            ->components([
                                TextEntry::make('direction')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color(fn ($state): string => $state->value === 'inbound' ? 'warning' : 'info'),
                                TextEntry::make('body')
                                    ->hiddenLabel(),
                                TextEntry::make('created_at')
                                    ->hiddenLabel()
                                    ->dateTime()
                                    ->color('gray'),
                            ]),
                    ]),
            ]);
    }
}
