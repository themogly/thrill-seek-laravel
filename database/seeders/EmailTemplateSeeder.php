<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'enquiry_acknowledgement',
                'name' => 'Enquiry auto-acknowledgement',
                'subject' => 'We got your enquiry — G-Force Skydiving ({{ reference }})',
                'body' => "Hi {{ name }},\n\nThanks for getting in touch with G-Force Skydiving — we've received your enquiry and one of the team will come back to you shortly.\n\nYour reference is {{ reference }}. Keep it handy if you call or email us.\n\nBlue skies,\nThe G-Force team",
                'variables' => ['name', 'reference', 'product'],
            ],
            [
                'key' => 'payment_link',
                'name' => 'Payment link',
                'subject' => 'Complete your payment — G-Force Skydiving ({{ reference }})',
                'body' => "Hi {{ name }},\n\nHere's your secure payment link for {{ description }} ({{ amount }}):\n\n{{ link }}\n\nThe link takes you to Stripe, our payment provider. Once you've paid, you'll get a confirmation email straight away.\n\nAny questions, just reply to this email.\n\nBlue skies,\nThe G-Force team",
                'variables' => ['name', 'amount', 'description', 'link', 'reference'],
            ],
            [
                'key' => 'booking_rescheduled',
                'name' => 'Booking rescheduled',
                'subject' => 'Your jump has been rescheduled — G-Force Skydiving ({{ reference }})',
                'body' => "Hi {{ name }},\n\nYour booking for {{ product }} has been rescheduled.\n\nPrevious date: {{ old_date }}\nNew date: {{ new_date }}\n\nYour booking reference is {{ reference }}. If the new date doesn't work for you, just reply to this email and we'll sort it out.\n\nBlue skies,\nThe G-Force team",
                'variables' => ['name', 'reference', 'product', 'old_date', 'new_date'],
            ],
            [
                'key' => 'payment_received',
                'name' => 'Payment received (customer confirmation)',
                'subject' => 'Payment received — G-Force Skydiving ({{ reference }})',
                'body' => "Hi {{ name }},\n\nThank you! We've received your payment of {{ amount }} for {{ product }}.\n\nYour booking reference is {{ reference }}. {{ balance_note }}\n\nWe'll be in touch to arrange your jump date if we haven't already.\n\nBlue skies,\nThe G-Force team",
                'variables' => ['name', 'amount', 'product', 'reference', 'balance_note'],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['key' => $template['key']], $template);
        }
    }
}
