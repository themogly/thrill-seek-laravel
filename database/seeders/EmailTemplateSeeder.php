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
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['key' => $template['key']], $template);
        }
    }
}
