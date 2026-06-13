<?php

namespace App\Filament\Resources\CourseDates\Pages;

use App\Actions\SendCourseMessage;
use App\Filament\Resources\CourseDates\CourseDateResource;
use App\Models\CourseDate;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCourseDate extends EditRecord
{
    protected static string $resource = CourseDateResource::class;

    public function getRecord(): CourseDate
    {
        $record = parent::getRecord();

        if (! $record instanceof CourseDate) {
            throw new \LogicException('Expected a CourseDate record.');
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('messageStudents')
                ->label('Message students')
                ->icon('heroicon-o-envelope')
                ->color('success')
                // No one to message until the course has paid, non-cancelled students.
                ->visible(fn (): bool => $this->getRecord()->messageableBookings()->isNotEmpty())
                ->form([
                    TextInput::make('subject')
                        ->label('Subject')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('body')
                        ->label('Message')
                        ->helperText(fn (): string => 'Goes to '.$this->getRecord()->messageableBookings()->count().' student(s) on this course — cancelled bookings and unpaid holds are skipped. Blank lines start a new paragraph.')
                        ->rows(8)
                        ->required(),
                    Select::make('documents')
                        ->label('Attach documents')
                        ->helperText('From the document library. Combined size up to 15 MB per message.')
                        ->options(fn (): array => Document::orderBy('name')->pluck('name', 'id')->all())
                        ->multiple()
                        ->rule(function () {
                            return function (string $attribute, mixed $value, \Closure $fail): void {
                                $total = (int) Document::whereIn('id', (array) $value)->sum('size_bytes');

                                if ($total > Document::MAX_MESSAGE_ATTACHMENT_BYTES) {
                                    $fail(sprintf(
                                        'Attachments total %.1f MB — the limit per message is %d MB.',
                                        $total / (1024 * 1024),
                                        Document::MAX_MESSAGE_ATTACHMENT_BYTES / (1024 * 1024),
                                    ));
                                }
                            };
                        }),
                ])
                ->action(function (array $data, SendCourseMessage $sendCourseMessage): void {
                    /** @var User $user */
                    $user = auth()->user();
                    $course = $this->getRecord();

                    $message = $sendCourseMessage->handle(
                        $course,
                        $data['subject'],
                        $data['body'],
                        Document::whereIn('id', $data['documents'] ?? [])->get(),
                        $user,
                    );

                    Notification::make()
                        ->success()
                        ->title('Message queued')
                        ->body("Sending to {$message->recipientCount()} student(s).")
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
