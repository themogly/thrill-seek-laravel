<?php

namespace App\ViewModels;

use App\Settings\GeneralSettings;
use App\Settings\SimplePagesSettings;
use Illuminate\Support\Carbon;

/**
 * Resolves the privacy page. The policy copy is owner-editable CMS content
 * (`SimplePagesSettings::privacy_body`), but the variable parts are NOT stored
 * in the text — they resolve live so they can never drift:
 *   {{ business_name }} → GeneralSettings::site_name
 *   {{ contact_email }} → GeneralSettings::email (as a mailto link)
 *   {{ last_updated }}  → the date the policy body was last saved
 * The owner edits the body (incl. the [Owner: …] placeholders) and these tokens
 * stay correct everywhere. See DECISIONS.md (feature/privacy-policy).
 */
class PrivacyPage
{
    /** @return array{title: string, body: string} */
    public function viewData(): array
    {
        $pages = app(SimplePagesSettings::class);
        $general = app(GeneralSettings::class);

        $email = $general->email;

        $replacements = [
            'business_name' => e($general->site_name),
            'contact_email' => '<a href="mailto:'.e($email).'">'.e($email).'</a>',
            'last_updated' => e($this->lastUpdated($pages->privacy_updated_at)),
        ];

        return [
            'title' => $pages->privacy_title,
            'body' => $this->render($pages->privacy_body, $replacements),
        ];
    }

    /**
     * Substitute {{ token }} placeholders, leaving any unknown token untouched
     * (same contract as the email TemplateRenderer).
     *
     * @param  array<string, string>  $replacements
     */
    private function render(string $body, array $replacements): string
    {
        return (string) preg_replace_callback(
            '/{{\s*([\w.]+)\s*}}/',
            fn (array $matches): string => $replacements[$matches[1]] ?? $matches[0],
            $body,
        );
    }

    private function lastUpdated(string $date): string
    {
        $parsed = rescue(fn (): Carbon => Carbon::parse($date), null, report: false);

        return $parsed?->format('j F Y') ?? $date;
    }
}
