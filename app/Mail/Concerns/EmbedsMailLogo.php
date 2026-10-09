<?php

namespace App\Mail\Concerns;

use App\Support\MailLogo;
use Illuminate\Mail\Message;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

/**
 * Attaches the logo PNG inline under MailLogo::CID when the outgoing HTML
 * references it (a newsletter without a logo block gets no stray attachment),
 * and points previews at the public URL instead.
 */
trait EmbedsMailLogo
{
    /**
     * @param  Message  $message
     * @return $this
     */
    protected function buildAttachments($message)
    {
        parent::buildAttachments($message);

        $email = $message->getSymfonyMessage();

        if (str_contains((string) $email->getHtmlBody(), MailLogo::src())) {
            $email->addPart((new DataPart(new File(MailLogo::path()), MailLogo::CID, 'image/png'))->asInline());
        }

        return $this;
    }

    public function render(): string
    {
        return MailLogo::forPreview(parent::render());
    }
}
