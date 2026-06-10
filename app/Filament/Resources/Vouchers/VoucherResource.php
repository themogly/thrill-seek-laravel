<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vouchers;

use App\Actions\RedeemVoucher;
use App\Enums\VoucherStatus;
use App\Filament\Resources\Vouchers\Pages\CreateVoucher;
use App\Filament\Resources\Vouchers\Pages\EditVoucher;
use App\Filament\Resources\Vouchers\Pages\ListVouchers;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\Voucher;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Voucher')
                ->columns(2)
                ->components([
                    Select::make('product_id')
                        ->label('Product')
                        ->relationship('product', 'name')
                        ->preload(),
                    TextInput::make('amount_pence')
                        ->label('Value (pence)')
                        ->helperText('e.g. 26000 = £260.')
                        ->numeric()
                        ->required()
                        ->minValue(100),
                    DatePicker::make('expires_at')
                        ->label('Expires')
                        ->default(now()->addYear())
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(VoucherStatus::class)
                        ->default(VoucherStatus::Active->value)
                        ->required(),
                ]),
            Section::make('People')
                ->columns(2)
                ->components([
                    TextInput::make('purchaser_name')
                        ->label('Bought by')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('purchaser_email')
                        ->label('Purchaser email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('recipient_name')
                        ->label('For (recipient)')
                        ->maxLength(255),
                    Textarea::make('message')
                        ->label('Gift message')
                        ->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('formatted_amount')
                    ->label('Value'),
                TextColumn::make('purchaser_name')
                    ->label('Bought by')
                    ->description(fn (Voucher $record): string => $record->purchaser_email)
                    ->searchable(['purchaser_name', 'purchaser_email']),
                TextColumn::make('recipient_name')
                    ->label('For')
                    ->placeholder('—'),
                TextColumn::make('display_status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(VoucherStatus::class),
            ])
            ->recordActions([
                Action::make('sendEmail')
                    ->label('Email voucher')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (Voucher $record): bool => $record->isRedeemable())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Voucher $record): string => "Send the voucher email to {$record->purchaser_email}?")
                    ->action(function (Voucher $record): void {
                        Mail::to($record->purchaser_email)->queue(new TemplatedMail(
                            EmailTemplate::findByKey('gift_voucher'),
                            [
                                'name' => $record->purchaser_name,
                                'recipient' => $record->recipient_name ?? 'the lucky recipient',
                                'code' => $record->code,
                                'amount' => $record->formatted_amount,
                                'product' => $record->product->name ?? 'a G-Force experience',
                                'expires' => $record->expires_at->format('j F Y'),
                                'message' => (string) $record->message,
                                'contact_email' => app(GeneralSettings::class)->email,
                            ],
                        ));

                        Notification::make()->success()->title('Voucher emailed')->send();
                    }),
                Action::make('redeem')
                    ->label('Redeem')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Voucher $record): bool => $record->isRedeemable())
                    ->form([
                        Select::make('booking_id')
                            ->label('Apply to booking')
                            ->options(fn (): array => Booking::query()
                                ->withOutstandingBalance()
                                ->latest()
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Booking $booking): array => [
                                    $booking->id => "{$booking->reference} — {$booking->name} (due {$booking->formatted_balance_due})",
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Voucher $record, array $data, RedeemVoucher $redeem): void {
                        /** @var User $user */
                        $user = auth()->user();
                        $booking = Booking::findOrFail($data['booking_id']);

                        $redeem->handle($record, $booking, $user);

                        Notification::make()
                            ->success()
                            ->title('Voucher redeemed')
                            ->body("{$record->formatted_amount} applied to {$booking->reference}.")
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVouchers::route('/'),
            'create' => CreateVoucher::route('/create'),
            'edit' => EditVoucher::route('/{record}/edit'),
        ];
    }
}
