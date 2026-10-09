<?php

namespace Tests\Feature\Design;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * An inline booking-form error is tied to its control: the control is
 * aria-invalid and aria-describedby the error, so a screen reader reads the
 * problem when the field is focused — not just once as an alert.
 */
class FieldErrorAssociationTest extends TestCase
{
    public function test_an_input_inside_a_field_with_an_error_points_at_the_error(): void
    {
        $html = Blade::render('<x-booking.field label="Email" for="bt-email" error="Enter a valid email."><x-ui.input id="bt-email" type="email" /></x-booking.field>');

        $this->assertMatchesRegularExpression('/<input[^>]*id="bt-email"[^>]*>/s', $html);
        preg_match('/<input[^>]*id="bt-email"[^>]*>/s', $html, $input);
        $this->assertStringContainsString('aria-invalid="true"', $input[0]);
        $this->assertStringContainsString('aria-describedby="bt-email-error"', $input[0]);
        $this->assertMatchesRegularExpression('/id="bt-email-error"[^>]*>\s*Enter a valid email\./', $html);
    }

    public function test_textareas_and_date_fields_point_at_the_error_too(): void
    {
        $textarea = Blade::render('<x-booking.field label="Notes" for="bt-notes" error="Too long."><x-ui.textarea id="bt-notes" /></x-booking.field>');
        $this->assertMatchesRegularExpression('/<textarea[^>]*aria-describedby="bt-notes-error"/s', $textarea);

        $date = Blade::render('<x-booking.field label="Date of birth" for="bt-dob" error="You must be 18."><x-ui.date-field id="bt-dob" model="date_of_birth" /></x-booking.field>');
        $this->assertMatchesRegularExpression('/<input[^>]*id="bt-dob"[^>]*aria-describedby="bt-dob-error"|<input[^>]*aria-describedby="bt-dob-error"[^>]*id="bt-dob"/s', $date);
    }

    public function test_a_valid_field_carries_no_error_wiring(): void
    {
        $html = Blade::render('<x-booking.field label="Email" for="bt-email"><x-ui.input id="bt-email" /></x-booking.field>');

        $this->assertStringNotContainsString('aria-invalid', $html);
        $this->assertStringNotContainsString('bt-email-error', $html);
    }
}
