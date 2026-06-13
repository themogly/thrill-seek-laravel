<?php

namespace App\Livewire;

use App\Actions\CreateEnquiry;
use App\Actions\StartAffCheckout;
use App\Exceptions\BookingUnavailableException;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\CourseDate;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;

class BookAff extends Component
{
    use ProtectsAgainstSpam;

    public int $step = 1;

    #[Url(as: 'course')]
    public ?int $courseDateId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $date_of_birth = '';

    public string $weight_kg = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public string $experience = '';

    public bool $terms = false;

    public string $unavailableMessage = '';

    public string $paymentErrorMessage = '';

    /** Set when the enquiry-first path (online payments off) has been submitted. */
    public bool $enquirySent = false;

    /** Whether the public site can take card payment right now. */
    public function getPaymentsEnabledProperty(): bool
    {
        return app(GeneralSettings::class)->online_payments_enabled;
    }

    public function mount(): void
    {
        if ($this->courseDateId !== null && $this->getCourseProperty()?->isBookable()) {
            $this->step = 2;
        } else {
            $this->courseDateId = null;
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'courseDateId' => 'required|integer|exists:course_dates,id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'date_of_birth' => 'required|date|before:-18 years',
            'weight_kg' => 'required|numeric|min:30|max:120',
            'emergency_contact_name' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|string|max:30',
            'experience' => 'nullable|string|max:2000',
            'terms' => 'accepted',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'date_of_birth.before' => 'You must be at least 18 to start an AFF course.',
            'terms.accepted' => 'Please confirm you accept the booking terms.',
        ];
    }

    public function chooseCourse(int $courseDateId): void
    {
        $course = $this->getCoursesProperty()->firstWhere('id', $courseDateId);

        if ($course === null) {
            $this->unavailableMessage = 'That course is no longer available — please pick another date.';

            return;
        }

        $this->courseDateId = $courseDateId;
        $this->unavailableMessage = '';
        $this->step = 2;
    }

    public function backToStep(int $step): void
    {
        $this->step = max(1, min($this->step, $step));
    }

    public function continueToReview(): void
    {
        $this->validate(collect($this->rules())->except('terms')->all(), $this->messages());

        $this->step = 3;
    }

    public function pay(StartAffCheckout $startCheckout, CreateEnquiry $createEnquiry): void
    {
        if ($this->isSpam()) {
            return;
        }

        $this->ensureNotRateLimited();
        $this->validate();

        $course = CourseDate::find($this->courseDateId);

        if ($course === null) {
            $this->unavailableMessage = 'That course is no longer available — please pick another date.';
            $this->step = 1;

            return;
        }

        // Enquiry-first mode: capture the request as an enquiry and never
        // create a Stripe session from the public site.
        if (! $this->getPaymentsEnabledProperty()) {
            $this->submitAsEnquiry($createEnquiry, $course);

            return;
        }

        try {
            $result = $startCheckout->handle($course, [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'date_of_birth' => $this->date_of_birth,
                'weight_kg' => $this->weight_kg,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'experience' => $this->experience,
            ]);
        } catch (BookingUnavailableException $e) {
            $this->unavailableMessage = $e->getMessage();
            $this->courseDateId = null;
            $this->step = 1;

            return;
        } catch (\Throwable $e) {
            Log::error('AFF checkout could not start', ['exception' => $e->getMessage()]);

            $this->paymentErrorMessage = 'Online payment is temporarily unavailable. Nothing has been charged — please call us or send an enquiry and we\'ll hold your place.';

            return;
        }

        $this->redirect($result['checkout_url']);
    }

    private function submitAsEnquiry(CreateEnquiry $createEnquiry, CourseDate $course): void
    {
        $createEnquiry->handle([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'preferred_date' => $course->start_date->toDateString(),
            'message' => filled($this->experience) ? $this->experience : null,
            'context' => [
                'date_of_birth' => $this->date_of_birth,
                'weight_kg' => $this->weight_kg,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'course' => $course->date_range_label,
                'location' => $course->location?->name,
            ],
        ], $course->product);

        $this->enquirySent = true;
    }

    public function getCourseProperty(): ?CourseDate
    {
        return $this->courseDateId === null ? null : CourseDate::with(['product', 'location'])->find($this->courseDateId);
    }

    /** @return EloquentCollection<int, CourseDate> */
    public function getCoursesProperty(): EloquentCollection
    {
        return CourseDate::upcomingOpen()
            ->with(['product', 'location'])
            ->get()
            ->filter(fn (CourseDate $course): bool => $course->isBookable())
            ->values();
    }

    public function render(): View
    {
        return view('livewire.book-aff', [
            'courses' => $this->getCoursesProperty(),
            'course' => $this->getCourseProperty(),
        ]);
    }
}
