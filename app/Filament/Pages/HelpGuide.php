<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Settings\ManageGeneralSettings;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\CourseDates\CourseDateResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\TandemDates\TandemDateResource;
use App\Filament\Resources\Vouchers\VoucherResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * In-panel, plain-English guide for the (non-technical) owner.
 *
 * Developer-maintained documentation (NOT CMS content): edit the section array
 * below to update it. Adding a topic when a feature ships = one entry here. HTML
 * in `intro`/`steps` is author-trusted, not user input. Single page with a
 * jump-link contents list — see DECISIONS.md for the single-vs-multi-page call.
 */
class HelpGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Help';

    protected static ?string $navigationLabel = 'How it all works';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'How it all works';

    protected string $view = 'filament.pages.help-guide';

    /**
     * The guide content. Each section: id (anchor), icon, title, intro (HTML),
     * steps (HTML list items), and an optional "Open …" link.
     *
     * @return list<array{id: string, icon: string, title: string, intro: string, steps: list<string>, cta: array{label: string, url: string}|null}>
     */
    public function sections(): array
    {
        return [
            [
                'id' => 'overview',
                'icon' => 'heroicon-o-home',
                'title' => 'Getting started',
                'intro' => 'This admin panel runs the whole public website. Anything you change here updates the live site. There’s no separate “publish” step for most content — <strong>press Save and it’s live</strong> (news articles are the exception; they have a draft/publish switch).',
                'steps' => [
                    'The left menu is grouped: <strong>Site content</strong> (page wording &amp; photos), <strong>Bookings &amp; sales</strong> (the day-to-day), <strong>News</strong>, and <strong>Help</strong>.',
                    'Your changes are saved per screen — fill in a form and press <strong>Save</strong>.',
                    'New to it? Jump to the <a href="#launch">launch checklist</a> at the bottom.',
                ],
                'cta' => null,
            ],
            [
                'id' => 'content',
                'icon' => 'heroicon-o-pencil-square',
                'title' => 'Editing page content & images',
                'intro' => 'All page wording and photos live under the <strong>Site content</strong> group — one screen per page, plus the Testimonials, Hall of Fame, Shop, Gallery and Instructors lists.',
                'steps' => [
                    '<strong>To change a heading or paragraph:</strong> open the matching Site content screen, edit the box, press Save.',
                    'The <strong>“lead”</strong> line under a title is optional — clear it and the line (and its spacing) disappears cleanly.',
                    '<strong>To change a photo:</strong> drag a file onto the upload box. Images are resized for the web automatically.',
                ],
                'cta' => ['label' => 'Open General settings', 'url' => ManageGeneralSettings::getUrl()],
            ],
            [
                'id' => 'products',
                'icon' => 'heroicon-o-tag',
                'title' => 'Products & pricing',
                'intro' => 'A product is a thing you sell — Tandem Skydive, AFF Course, Coaching.',
                'steps' => [
                    '<strong>Prices are in pounds:</strong> type <code>260.00</code>, not <code>26000</code>. The system stores the exact pence behind the scenes.',
                    '<strong>AFF</strong> products also have a deposit (also in pounds).',
                    '<strong>Add-ons</strong> (e.g. an outside camera) are listed under the product; tick “purchasable” for ones customers can buy online.',
                ],
                'cta' => ['label' => 'Open Products', 'url' => ProductResource::getUrl()],
            ],
            [
                'id' => 'tandem-dates',
                'icon' => 'heroicon-o-calendar',
                'title' => 'Tandem dates',
                'intro' => 'Tandem dates are single-day jump slots.',
                'steps' => [
                    'Each slot has a <strong>date &amp; time</strong>, a <strong>location</strong> and a number of <strong>places</strong> (capacity).',
                    'You can type the date or pick it from the calendar; a new slot defaults to today at 09:00.',
                ],
                'cta' => ['label' => 'Open Tandem dates', 'url' => TandemDateResource::getUrl()],
            ],
            [
                'id' => 'aff-courses',
                'icon' => 'heroicon-o-academic-cap',
                'title' => 'AFF courses',
                'intro' => 'AFF courses are multi-day date ranges.',
                'steps' => [
                    'A course must run for <strong>at least 5 days</strong> — the form suggests an end date and shows the day count as you edit.',
                    'Set the <strong>location</strong>, <strong>places</strong> and an optional price/deposit override.',
                ],
                'cta' => ['label' => 'Open AFF courses', 'url' => CourseDateResource::getUrl()],
            ],
            [
                'id' => 'locations',
                'icon' => 'heroicon-o-map-pin',
                'title' => 'Locations',
                'intro' => 'Dropzones where you run jumps and courses.',
                'steps' => [
                    'A location can’t have a tandem date <em>and</em> an AFF course on the same day — the form stops you, to avoid double-booking the dropzone.',
                ],
                'cta' => ['label' => 'Open Locations', 'url' => LocationResource::getUrl()],
            ],
            [
                'id' => 'bookings',
                'icon' => 'heroicon-o-clipboard-document-check',
                'title' => 'Bookings & the calendar',
                'intro' => 'Paid jumps appear under Bookings and on the Calendar.',
                'steps' => [
                    'Each booking has a <strong>status</strong> (pending, confirmed, etc.). Reschedule a booking and the customer is emailed the new date.',
                    'The <strong>Calendar</strong> lays out tandem dates and AFF courses by month, with a location filter.',
                    'The Calendar only appears once you have your first booking — it stays hidden while empty.',
                ],
                'cta' => ['label' => 'Open Bookings', 'url' => BookingResource::getUrl()],
            ],
            [
                'id' => 'enquiries',
                'icon' => 'heroicon-o-inbox-arrow-down',
                'title' => 'Enquiries',
                'intro' => 'Messages from the website land in the Enquiries inbox (a number badge shows unread ones).',
                'steps' => [
                    'Open an enquiry to read the thread and <strong>reply</strong> — your reply is emailed to the customer and kept in the thread.',
                    'From the enquiry you can take payment (next section), which converts it to a booking.',
                ],
                'cta' => ['label' => 'Open Enquiries', 'url' => EnquiryResource::getUrl()],
            ],
            [
                'id' => 'customer-replies',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'title' => 'Customer replies & messages',
                'intro' => 'When a customer replies to one of your emails, their reply appears automatically inside the enquiry — no copy-pasting from your own inbox.',
                'steps' => [
                    'The Enquiries list shows who needs you: a <strong>“Needs reply”</strong> tab, a red bell on unread rows, the latest-message preview, and a number badge on the Enquiries menu item.',
                    'The <strong>Customers</strong> list flags anyone awaiting a reply too, with “Has unread” and “Awaiting our reply” filters.',
                    'Opening an enquiry marks it read; sending a reply sets it back to handled.',
                    'Anything that can’t be matched to an enquiry is kept under <strong>Unmatched messages</strong> to check — nothing is ever lost.',
                    'The one-time technical email setup (so replies route back here) is a developer job — contact your developer if replies aren’t appearing.',
                ],
                'cta' => ['label' => 'Open Enquiries', 'url' => EnquiryResource::getUrl()],
            ],
            [
                'id' => 'payments',
                'icon' => 'heroicon-o-credit-card',
                'title' => 'Taking payment',
                'intro' => 'From inside an enquiry you can take payment two ways.',
                'steps' => [
                    '<strong>Send a payment link</strong> — emails the customer a secure card-payment link. When they pay, it becomes a booking automatically.',
                    '<strong>Record a bank transfer</strong> — if they paid you directly, log the amount (in pounds) and reference; this also creates the booking.',
                    'Customers see “Pay by card” on the public site — they don’t need to know the payment processor.',
                ],
                'cta' => ['label' => 'Open Enquiries', 'url' => EnquiryResource::getUrl()],
            ],
            [
                'id' => 'vouchers',
                'icon' => 'heroicon-o-gift',
                'title' => 'Gift vouchers',
                'intro' => 'Customers buy vouchers online (emailed a code + printable PDF); you can also issue them by hand.',
                'steps' => [
                    'Manage, issue and revoke vouchers under Vouchers.',
                    'A voucher is redeemed against a booking as a payment.',
                ],
                'cta' => ['label' => 'Open Vouchers', 'url' => VoucherResource::getUrl()],
            ],
            [
                'id' => 'newsletter',
                'icon' => 'heroicon-o-paper-airplane',
                'title' => 'Newsletter',
                'intro' => 'Subscribers (with double opt-in and one-click unsubscribe) live under Newsletter subscribers; you build and send newsletters under Newsletters.',
                'steps' => [
                    '<strong>To send a newsletter:</strong> New newsletter → add a name and subject.',
                    '<strong>Add blocks</strong> (heading, text, image, button, divider, image+text, “latest news”, featured course) and drag to reorder.',
                    '<strong>Preview</strong> it (desktop + mobile), then <strong>Send test to me</strong> — that only emails you.',
                    '<strong>Send to subscribers</strong> goes to everyone confirmed; unconfirmed/unsubscribed people are skipped. Sent newsletters are kept as history.',
                ],
                'cta' => ['label' => 'Open Newsletters', 'url' => NewsletterCampaignResource::getUrl()],
            ],
            [
                'id' => 'news',
                'icon' => 'heroicon-o-newspaper',
                'title' => 'News articles',
                'intro' => 'Articles show on the public /news page and the newest appears on the home page.',
                'steps' => [
                    '<strong>New article:</strong> add a title (the web address fills in automatically), an optional lead line, and the body.',
                    '<strong>Draft vs published:</strong> leave “Published” off to keep it hidden; set a future publish date to schedule it — it appears automatically on the day.',
                    '<strong>Link an AFF course</strong> to show its live dates and places-left with a Book button on the article.',
                ],
                'cta' => ['label' => 'Open News', 'url' => NewsResource::getUrl()],
            ],
            [
                'id' => 'course-comms',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'title' => 'Course communications',
                'intro' => 'Keep students on an AFF course informed.',
                'steps' => [
                    'Open a course and use <strong>Message students</strong> to email everyone booked (cancelled/unpaid are skipped).',
                    'Attach documents from the <strong>document library</strong> (kit lists, joining instructions).',
                    'Reminders are sent automatically before courses/jumps; the message history shows what went out.',
                ],
                'cta' => ['label' => 'Open Documents', 'url' => DocumentResource::getUrl()],
            ],
            [
                'id' => 'toggles',
                'icon' => 'heroicon-o-adjustments-horizontal',
                'title' => 'Feature toggles',
                'intro' => 'Under <strong>Settings → General → Features</strong> you can switch whole areas on or off.',
                'steps' => [
                    '<strong>Online shop</strong> — when off, the Shop is hidden and its page returns “not found”.',
                    '<strong>Online payments</strong> — when off, “book &amp; pay” buttons send an enquiry instead of taking a card; you can still send links/record transfers from here.',
                    '<strong>News</strong> — when off, News is hidden from the site.',
                ],
                'cta' => ['label' => 'Open Features', 'url' => ManageGeneralSettings::getUrl()],
            ],
            [
                'id' => 'email-templates',
                'icon' => 'heroicon-o-envelope',
                'title' => 'Email templates',
                'intro' => 'The wording of automatic emails (confirmations, reminders, acknowledgements) is editable.',
                'steps' => [
                    'Edit the text and keep the <code>{{ placeholders }}</code> — they’re filled with real details when the email sends.',
                ],
                'cta' => ['label' => 'Open Email templates', 'url' => EmailTemplateResource::getUrl()],
            ],
            [
                'id' => 'launch',
                'icon' => 'heroicon-o-rocket-launch',
                'title' => 'First things to set up (launch checklist)',
                'intro' => 'Before you go live, work through these:',
                'steps' => [
                    'Set your <strong>prices</strong> (Products) — remember pounds, e.g. <code>260.00</code>.',
                    'Add your <strong>tandem dates</strong> and <strong>AFF courses</strong>, and your <strong>locations</strong>.',
                    'Upload real <strong>photos</strong> (Instructors, Hall of Fame, News, page images).',
                    'Check your <strong>contact details</strong> and social links (Settings → General).',
                    'Decide your <strong>feature toggles</strong> (shop, online payments, news).',
                ],
                'cta' => ['label' => 'Open Subscribers', 'url' => NewsletterSubscriberResource::getUrl()],
            ],
            [
                'id' => 'help',
                'icon' => 'heroicon-o-lifebuoy',
                'title' => 'Something’s wrong?',
                'intro' => 'If something doesn’t look right or you’re unsure, your developer can help.',
                'steps' => [
                    'Note what you were doing and what you expected, and get in touch with whoever built the site.',
                    'Technical notes live in the project’s <code>SETUP.md</code> and <code>DECISIONS.md</code>.',
                ],
                'cta' => null,
            ],
        ];
    }
}
