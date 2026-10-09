<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Actions\SendBookingConfirmation;
use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    /** The form's "Email the customer" toggle — not a booking column. */
    private bool $notifyCustomer = false;

    /** null = no email was due; true/false = whether the confirmation was queued. */
    private ?bool $emailed = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->notifyCustomer = (bool) ($data['notify_customer'] ?? false);
        unset($data['notify_customer']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();

        // Same action, same mail as a booking confirmed online or by an edit.
        if ($this->notifyCustomer && $booking->status === BookingStatus::Confirmed) {
            $this->emailed = app(SendBookingConfirmation::class)->handle($booking);
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $notification = parent::getCreatedNotification();

        return match ($this->emailed) {
            true => $notification?->body('The customer has been emailed.'),
            false => $notification?->warning()->title('Booking created — email not sent')
                ->body('The booking is saved, but the confirmation email could not be sent. Let the customer know another way.'),
            null => $notification,
        };
    }
}
