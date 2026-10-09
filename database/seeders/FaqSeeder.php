<?php

namespace Database\Seeders;

use App\Enums\FaqPage;
use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Per-page FAQs seeded with the business's real answers (prices, course
 * structure, membership, charity option). Items the owner must still confirm
 * are tagged [VERIFY …]; the owner refines them in the admin.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, list<array{q: string, a: string}>> $sets */
        $sets = [
            FaqPage::Tandem->value => [
                ['q' => 'Is it safe? Do I need any experience?', 'a' => 'No experience is needed — you jump harnessed to a fully qualified, British Skydiving / USPA-rated instructor who does all the flying, after a full safety brief on the day.'],
                ['q' => 'Are there weight and age limits?', 'a' => 'Up to 15 stone there’s no extra charge; 15.1–16st is +£20, 16.1–17st +£40, 17.1–18st +£60, and over 18st needs an assessment first. [VERIFY exact weight and minimum-age limits.]'],
                ['q' => 'What should I wear?', 'a' => 'Comfortable clothes for the weather and trainers — no sandals. We provide all the jump kit.'],
                ['q' => 'Can I get photos or video of my jump?', 'a' => 'Yes — an Outside Camera package is {addon:outside-camera} and a HandCam package {addon:handcam}, payable before your jump day.'],
                ['q' => 'What happens if the weather is bad?', 'a' => 'Jumps depend on the weather and run in slot order. If yours is cancelled for weather we’ll rebook you; a {addon:rebooking-fee} fee applies only if you ask to change your own date. [VERIFY wind limits — jumps typically don’t run above ~20kt.]'],
                ['q' => 'How long does the day take?', 'a' => 'Allow a few hours on site — jumps run in slot order and can be delayed by weather.'],
                ['q' => 'I have a medical condition — can I still jump?', 'a' => 'Some conditions need a doctor’s sign-off before you can jump. Please get in touch and we’ll advise.'],
                ['q' => 'Can I jump for charity?', 'a' => 'Yes — you can raise sponsorship for any charity, and in many cases the {price:tandem-skydive} jump cost can be covered through your fundraising. Camera packages can’t be paid from sponsorship funds and are paid separately.'],
            ],
            FaqPage::Aff->value => [
                ['q' => 'How long does the AFF course take?', 'a' => 'Eight levels (normally one jump per level) over roughly a week — weather permitting, sometimes as few as three days — followed by 10 consolidation jumps (18 jumps in total) to reach your A Licence.'],
                ['q' => 'What licence do I get?', 'a' => 'Your A Licence — recognised by British Skydiving and internationally — which lets you skydive solo worldwide. A G-Force British Skydiving instructor can sign it off even though the course runs abroad.'],
                ['q' => 'Are there prerequisites?', 'a' => 'You must be 18 or over, reasonably fit, and some medical conditions need a doctor’s sign-off. [VERIFY upper age limit (around 55).]'],
                ['q' => 'How much does it cost and is there a deposit?', 'a' => 'The AFF course (Levels 1–8) is {price:aff-course}, booked with a deposit, and includes all equipment, instruction and 8 jumps. The 10 consolidation jumps are {price:consolidation-jumps}. See the pricing on this page.'],
                ['q' => 'Is British Skydiving membership included?', 'a' => 'No — membership isn’t included in the course price and the cost varies by time of year. Provisional membership is included for your ground school and Level 1; you’ll need full membership after that (around £125/year on a sliding scale — [VERIFY current cost]).'],
                ['q' => 'What if I need to repeat a level?', 'a' => 'Progression is at your own pace; a level can be repeated if needed. [VERIFY repeat-level / extra-jump pricing.]'],
                ['q' => 'Is kit provided?', 'a' => 'Yes — jumpsuit, helmet, goggles and altimeter are all included, along with instruction. [VERIFY whether packing (~£5/jump) is charged separately.]'],
                ['q' => 'Where do courses run, and what about travel?', 'a' => 'Courses run abroad (e.g. Spain or Portugal) for reliable weather. The UK ground school is completed before you travel, so your time abroad is all jumping. We arrange group accommodation (you can join the group or sort your own), you can fly from any airport, and a WhatsApp group closer to the date helps everyone coordinate flights and logistics.'],
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
