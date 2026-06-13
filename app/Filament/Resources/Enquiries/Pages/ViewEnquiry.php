<?php

namespace App\Filament\Resources\Enquiries\Pages;

use App\Actions\RecordManualPayment;
use App\Actions\SendEnquiryReply;
use App\Actions\SendPaymentLink;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentPurpose;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\Money;
use App\Support\MoneyField;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;

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
            Action::make('sendPaymentLink')
                ->label('Send payment link')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->form([
                    Select::make('purpose')
                        ->label('What is this payment for?')
                        ->options(PaymentPurpose::class)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, PaymentPurpose|string|null $state): void {
                            $default = $this->defaultAmountFor($state);

                            if ($default !== null) {
                                // The field shows pounds; the default is pence.
                                $set('amount_pence', $default / 100);
                            }
                        }),
                    MoneyField::pounds('amount_pence')
                        ->label('Amount')
                        ->helperText('In pounds, e.g. 260.00. Pre-filled from the product where possible.')
                        ->required()
                        ->minValue(1),
                    TextInput::make('description')
                        ->label('Shown on the Stripe checkout page')
                        ->default(fn (): string => $this->getRecord()->product->name ?? 'G-Force Skydiving')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data, SendPaymentLink $sendPaymentLink): void {
                    $enquiry = $this->getRecord();
                    /** @var User $user */
                    $user = auth()->user();

                    try {
                        $sendPaymentLink->handle(
                            $enquiry,
                            self::purposeFrom($data['purpose']),
                            (int) $data['amount_pence'],
                            $data['description'],
                            $user,
                        );
                    } catch (\Throwable $e) {
                        report($e);

                        Notification::make()
                            ->danger()
                            ->title('Could not create the payment link')
                            ->body('Check the Stripe keys in the .env file. '.$e->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Payment link sent')
                        ->body('Emailed to '.$enquiry->email.' ('.Money::formatPence((int) $data['amount_pence']).').')
                        ->send();
                }),
            Action::make('recordBankTransfer')
                ->label('Record bank transfer')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->form([
                    Select::make('purpose')
                        ->label('What was this payment for?')
                        ->options(PaymentPurpose::class)
                        ->required(),
                    MoneyField::pounds('amount_pence')
                        ->label('Amount received')
                        ->helperText('In pounds, e.g. 260.00.')
                        ->required()
                        ->minValue(1),
                    TextInput::make('reference')
                        ->label('Bank reference')
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('paid_at')
                        ->label('Date received')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                ])
                ->action(function (array $data, RecordManualPayment $recordPayment): void {
                    $enquiry = $this->getRecord();
                    /** @var User $user */
                    $user = auth()->user();

                    $recordPayment->handle(
                        $enquiry,
                        self::purposeFrom($data['purpose']),
                        (int) $data['amount_pence'],
                        $data['reference'],
                        Carbon::parse($data['paid_at']),
                        $user,
                    );

                    Notification::make()
                        ->success()
                        ->title('Payment recorded')
                        ->body('The enquiry has been converted to a booking.')
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

    private static function purposeFrom(PaymentPurpose|string $value): PaymentPurpose
    {
        return $value instanceof PaymentPurpose ? $value : PaymentPurpose::from($value);
    }

    /**
     * Sensible default amount for a payment purpose, based on the enquiry's
     * product and what has already been paid.
     */
    private function defaultAmountFor(PaymentPurpose|string|null $purpose): ?int
    {
        if ($purpose === null) {
            return null;
        }

        $enquiry = $this->getRecord();
        $product = $enquiry->product;

        return match (self::purposeFrom($purpose)) {
            PaymentPurpose::TandemFull => $product?->price_pence,
            PaymentPurpose::AffDeposit => $product?->deposit_pence,
            PaymentPurpose::AffBalance => $enquiry->booking->balance_due_pence
                ?? ($product?->price_pence !== null && $product->deposit_pence !== null
                    ? $product->price_pence - $product->deposit_pence
                    : null),
            default => null,
        };
    }
}
