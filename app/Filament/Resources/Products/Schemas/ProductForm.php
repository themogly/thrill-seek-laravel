<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductType;
use App\Support\ImageCrop;
use App\Support\MoneyField;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basics')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, callable $set, ?string $state): void {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Reference (slug)')
                            ->helperText('Used internally; lowercase letters and dashes.')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                        Select::make('type')
                            ->label('Type')
                            ->options(ProductType::class)
                            ->required()
                            ->live(),
                        Toggle::make('active')
                            ->label('Active')
                            ->helperText('Inactive products are hidden from the site.')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('summary')
                            ->label('Card summary')
                            ->helperText('One sentence shown on the home page card.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Description')
                            ->helperText('Longer description used in emails and admin.')
                            ->rows(3)
                            ->columnSpanFull(),
                        ImageCrop::ratio(
                            FileUpload::make('image')
                                ->label('Card image')
                                ->helperText('Shown on the square home-page card — crop to 1:1. Leave empty to keep the current image.')
                                ->disk('public')
                                ->directory('products')
                                ->dehydrated(fn (?string $state): bool => filled($state)),
                            '1:1',
                        ),
                        TextInput::make('page_path')
                            ->label('Page link')
                            ->helperText('Where the card links to, e.g. “/tandem”.')
                            ->maxLength(255),
                        Toggle::make('featured_on_home')
                            ->label('Show on home page')
                            ->helperText('Appears in the “Three Ways to Fly” cards.')
                            ->inline(false),
                    ]),
                Section::make('Pricing')
                    ->columns(2)
                    ->components([
                        MoneyField::pounds('price_pence')
                            ->label('Price')
                            ->helperText('In pounds, e.g. 260.00. Leave empty for enquiry-only pricing.'),
                        MoneyField::pounds('deposit_pence')
                            ->label('Deposit')
                            ->helperText('For AFF: the amount paid up front, e.g. 300.00. Required — without it the courses can’t be booked online.')
                            ->visible(fn (Get $get): bool => $get('type') === ProductType::Aff)
                            ->required(fn (Get $get): bool => $get('type') === ProductType::Aff)
                            ->minValue(1),
                        TextInput::make('price_note')
                            ->label('Price note')
                            ->helperText('Small print under the price row, e.g. “Paid direct to G-Force”.')
                            ->maxLength(255),
                        Toggle::make('show_from_price')
                            ->label('Show as “from £X”')
                            ->helperText('Off for coaching = “Price on enquiry”.')
                            ->inline(false),
                        TextInput::make('duration')
                            ->label('Duration')
                            ->maxLength(255),
                        Toggle::make('highlight')
                            ->label('Highlight price card')
                            ->helperText('Emphasised card on the AFF pricing grid.')
                            ->inline(false)
                            ->visible(fn (Get $get): bool => $get('type') === ProductType::Aff),
                    ]),
                Section::make('Price card features')
                    ->description('Bullet list shown on the AFF pricing cards.')
                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Aff)
                    ->components([
                        Repeater::make('features')
                            ->hiddenLabel()
                            ->simple(TextInput::make('feature')->required()->maxLength(255))
                            ->reorderable()
                            ->default([]),
                        Repeater::make('repeat_pricing')
                            ->label('Repeat jump pricing')
                            ->columns(2)
                            ->components([
                                TextInput::make('label')->required()->maxLength(255),
                                TextInput::make('value')->required()->maxLength(255),
                            ])
                            ->reorderable()
                            ->default([]),
                    ]),
                Section::make('Weight charges')
                    ->description('The weight surcharge table on the tandem page.')
                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Tandem)
                    ->components([
                        Repeater::make('weight_charges')
                            ->hiddenLabel()
                            ->columns(2)
                            ->components([
                                TextInput::make('band')->label('Weight band')->required()->maxLength(255),
                                TextInput::make('charge')->label('Charge')->required()->maxLength(255),
                            ])
                            ->reorderable()
                            ->default([]),
                    ]),
                Section::make('Add-ons & fees')
                    ->description('Extra priced lines shown with the product. “Purchasable” add-ons (e.g. camera packages) can be added to payments; fees (insurance, rebooking) are display-only.')
                    ->components([
                        Repeater::make('addOns')
                            ->hiddenLabel()
                            ->relationship()
                            ->columns(4)
                            ->components([
                                TextInput::make('name')->required()->maxLength(255),
                                MoneyField::pounds('price_pence')->label('Price')->required(),
                                TextInput::make('note')->maxLength(255),
                                Toggle::make('purchasable')->inline(false),
                            ])
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->default([]),
                    ]),
            ]);
    }
}
