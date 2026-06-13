<?php

namespace App\Filament\Resources\CourseDates\Schemas;

use App\Enums\CourseDateStatus;
use App\Enums\ProductType;
use App\Models\CourseDate;
use App\Models\Product;
use App\Support\AdminDates;
use App\Support\DateClash;
use App\Support\MoneyField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class CourseDateForm
{
    /** Inclusive day count between two form dates, or null while incomplete. */
    private static function dayCount(mixed $start, mixed $end): ?int
    {
        if (blank($start) || blank($end)) {
            return null;
        }

        $days = (int) Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay(), false) + 1;

        return $days > 0 ? $days : null;
    }

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
                        Select::make('location_id')
                            ->label('Location')
                            ->relationship('location', 'name', fn ($query) => $query->where('active', true))
                            ->searchable()
                            ->preload()
                            ->required(),
                        AdminDates::date('start_date')
                            ->label('Starts')
                            ->default(now())
                            ->live()
                            // Suggest an end that meets the 5-day minimum: bump the end
                            // whenever it is blank or would now be too short, but never
                            // shrink a longer course the admin has deliberately set.
                            ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                                if (blank($state)) {
                                    return;
                                }

                                $suggested = Carbon::parse($state)->addDays(CourseDate::MIN_DURATION_DAYS - 1);
                                $end = $get('end_date');

                                if (blank($end) || Carbon::parse($end)->lt($suggested)) {
                                    $set('end_date', $suggested->toDateString());
                                }
                            })
                            ->required(),
                        AdminDates::date('end_date')
                            ->label('Ends')
                            ->default(now()->addDays(CourseDate::MIN_DURATION_DAYS - 1))
                            ->live()
                            ->required()
                            ->helperText(function (Get $get): string {
                                $days = self::dayCount($get('start_date'), $get('end_date'));

                                return $days === null
                                    ? 'Courses run for at least '.CourseDate::MIN_DURATION_DAYS.' days.'
                                    : "Course length: {$days} day(s).";
                            })
                            ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                $days = self::dayCount($get('start_date'), $value);

                                if ($days !== null && $days < CourseDate::MIN_DURATION_DAYS) {
                                    $fail('AFF courses must run for at least '.CourseDate::MIN_DURATION_DAYS." days — this range is only {$days} day(s).");
                                }
                            })
                            ->rule(fn (Get $get, ?CourseDate $record) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                $start = $get('start_date');
                                $locationId = $get('location_id');

                                if (blank($start) || blank($value) || blank($locationId)) {
                                    return;
                                }

                                $clash = DateClash::tandemDateWithinRange(
                                    Carbon::parse($start),
                                    Carbon::parse($value),
                                    (int) $locationId,
                                );

                                if ($clash !== null) {
                                    $fail(DateClash::describeTandemDate($clash));
                                }
                            }),
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
                        MoneyField::pounds('price_pence')
                            ->label('Price')
                            ->helperText('In pounds, e.g. 1750.00.'),
                        MoneyField::pounds('deposit_pence')
                            ->label('Deposit')
                            ->helperText('In pounds, e.g. 300.00.'),
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
