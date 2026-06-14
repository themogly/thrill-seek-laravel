<?php

namespace Database\Seeders;

use App\Enums\FaqPage;
use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Starter FAQs per page so the section renders immediately and the owner has
 * examples to edit. Answers are intentionally GENERAL placeholders — the owner
 * refines the specifics (limits, prices, medical/safety wording) in the admin.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, list<array{q: string, a: string}>> $sets */
        $sets = [
            FaqPage::Tandem->value => [
                ['q' => 'Is it safe? Do I need any experience?', 'a' => 'No experience is needed — you jump harnessed to a fully qualified instructor who does all the flying. They brief you fully before you go. (Owner: add your safety record and certifications here.)'],
                ['q' => 'Are there weight and age limits?', 'a' => 'Yes — there are sensible weight and minimum-age limits for a tandem jump. (Owner: confirm your exact limits here.)'],
                ['q' => 'What should I wear?', 'a' => 'Comfortable clothes for the weather and trainers — no sandals. We provide the jump kit. (Owner: refine as needed.)'],
                ['q' => 'Can I get photos or video of my jump?', 'a' => 'Yes — photo and video packages can be added on the day. (Owner: list options and prices.)'],
                ['q' => 'What happens if the weather is bad?', 'a' => 'Jumps depend on the weather; if yours is cancelled for weather we reschedule it free of charge. (Owner: confirm your rescheduling policy.)'],
                ['q' => 'How long does the day take?', 'a' => 'Allow a few hours on site — jumps run in slot order and can be delayed by weather. (Owner: refine timing.)'],
                ['q' => 'I have a medical condition — can I still jump?', 'a' => 'Some conditions need a doctor’s sign-off before you can jump. Please get in touch and we’ll advise. (Owner: add your medical guidance.)'],
            ],
            FaqPage::Aff->value => [
                ['q' => 'How long does the AFF course take?', 'a' => 'The course runs over consecutive days, weather permitting. (Owner: confirm your typical duration and day count.)'],
                ['q' => 'What licence do I get?', 'a' => 'AFF takes you towards your skydiving licence through a structured set of levels. (Owner: name the exact licence/qualification.)'],
                ['q' => 'Are there prerequisites?', 'a' => 'There are minimum age, fitness and (for some) medical requirements. (Owner: list prerequisites.)'],
                ['q' => 'How much does it cost and is there a deposit?', 'a' => 'The course is booked with a deposit, with the balance due before you start. See the pricing on this page. (Owner: confirm.)'],
                ['q' => 'What if I need to repeat a level?', 'a' => 'Progression is at your own pace; a level can be repeated if needed. (Owner: confirm repeat-level pricing/policy.)'],
                ['q' => 'Is kit provided?', 'a' => 'All equipment and instruction are included. (Owner: confirm what’s provided.)'],
                ['q' => 'What about weather and rescheduling?', 'a' => 'Training depends on suitable weather; we’ll keep you posted and reschedule as needed. (Owner: confirm policy.)'],
            ],
            FaqPage::Coached->value => [
                ['q' => 'Who is coaching for?', 'a' => 'Coaching is for qualified jumpers looking to develop specific skills. (Owner: confirm prerequisites/licence level.)'],
                ['q' => 'What disciplines do you coach?', 'a' => 'We coach a range of disciplines. (Owner: list the disciplines you offer.)'],
                ['q' => 'How does pricing work?', 'a' => 'Coaching is priced per session. (Owner: confirm your rates and what a session includes.)'],
            ],
        ];

        foreach ($sets as $page => $faqs) {
            foreach ($faqs as $i => $faq) {
                Faq::updateOrCreate(
                    ['page' => $page, 'question' => $faq['q']],
                    ['answer' => '<p>'.$faq['a'].'</p>', 'sort_order' => $i + 1, 'is_active' => true],
                );
            }
        }
    }
}
