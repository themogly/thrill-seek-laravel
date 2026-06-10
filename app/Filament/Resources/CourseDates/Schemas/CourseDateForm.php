<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseDates\Schemas;

use App\Enums\CourseDateStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseDateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Course')
                    ->columns(2)
                    ->components([
                        Select::make('product_id')
                            ->label('Course product')
                            ->options(fn (): array => Product::ofType(ProductType::Aff)
                                ->whereNotNull('deposit_pence')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => Product::ofType(ProductType::Aff)
                                ->whereNotNull('deposit_pence')
                                ->value('id'))
                            ->required(),
                        TextInput::make('location')
                            ->label('Location')
                            ->helperText('Shown to customers, e.g. “Seville, Spain”.')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('starts_on')
                            ->label('Starts')
                            ->required(),
                        DatePicker::make('ends_on')
                            ->label('Ends')
                            ->helperText('Leave empty for single-day events.')
                            ->afterOrEqual('starts_on'),
                        TextInput::make('capacity')
                            ->label('Places')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(CourseDateStatus::class)
                            ->default(CourseDateStatus::Open->value)
                            ->required(),
                    ]),
                Section::make('Pricing overrides')
                    ->description('Leave empty to use the product price/deposit.')
                    ->columns(2)
                    ->components([
                        TextInput::make('price_pence')
                            ->label('Price (pence)')
                            ->helperText('e.g. 175000 = £1,750.')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('deposit_pence')
                            ->label('Deposit (pence)')
                            ->helperText('e.g. 30000 = £300.')
                            ->numeric()
                            ->minValue(0),
                    ]),
                Section::make('Notes')
                    ->components([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->helperText('Internal only.')
                            ->rows(3),
                    ]),
            ]);
    }
}
