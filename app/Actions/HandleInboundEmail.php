<?php

namespace App\Actions;

use App\Contracts\InboundEmailFetcher;
use App\Enums\EnquiryStatus;
use App\Enums\MessageDirection;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use App\Models\UnmatchedInboundMessage;
use App\Support\Inbound\AutoReplyDetector;
use App\Support\Inbound\EmailReplyParser;
use App\Support\Inbound\InboundEmail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns one verified inbound webhook into a threaded customer reply. Runs in a queued
 * job (the second Resend fetch is the slow part). Idempotent on the Resend email id,
 * skips auto-replies/bounces, and never drops mail it can't route — that goes to the
 * "Unmatched messages" list for manual review.
 */
class HandleInboundEmail
{
    public function __construct(
        private readonly InboundEmailFetcher $fetcher,
        private readonly EmailReplyParser $parser,
        private readonly AutoReplyDetector $autoReply,
    ) {}

    /** @param  array<string, mixed>  $payload  the verified webhook body, decoded */
    public function handle(array $payload): void
    {
        if (($payload['type'] ?? null) !== 'email.received') {
            return;
        }

        /** @var array<string, mixed> $data */
        $data = $payload['data'] ?? [];
        $externalId = (string) ($data['email_id'] ?? $data['id'] ?? '');
        if ($externalId === '') {
            return;
        }

        // Idempotent: a re-delivered webhook must never thread (or store) twice.
        if (EnquiryMessage::where('external_id', $externalId)->exists()
            || UnmatchedInboundMessage::where('external_id', $externalId)->exists()) {
            return;
        }

        $to = $this->firstAddress($data['to'] ?? null);
        $from = (string) ($data['from'] ?? '');
        $subject = (string) ($data['subject'] ?? '');

        // Step two: fetch the full body + attachments.
        $email = $this->fetcher->fetch($externalId);
        if ($email === null) {
            $this->storeUnmatched($externalId, $from, $to, $subject, 'fetch_failed', null);

            return;
        }

        // Out-of-office / bounce — acknowledge but never thread as a real reply.
        if ($this->autoReply->isAutomated($email)) {
            return;
        }

        $token = Enquiry::extractReplyToken($to);
        $enquiry = $token !== null ? Enquiry::findByReplyToken($token) : null;

        if ($enquiry === null) {
            $this->storeUnmatched($externalId, $from, $to, $subject, $token !== null ? 'unknown_token' : 'no_token', $email->text);

            return;
        }

        $clean = $this->parser->parse($email->text);

        $message = $enquiry->messages()->create([
            'direction' => MessageDirection::Inbound,
            'body' => $clean !== '' ? $clean : $email->text,
            'sender_email' => $this->emailAddress($from) ?? $enquiry->email,
            'received_at' => $email->receivedAt ?? now(),
            'raw_body' => $email->text,
            'attachments' => $this->storeAttachments($email),
            'external_id' => $externalId,
        ]);

        // Surface it as needing attention again.
        $enquiry->forceFill([
            'status' => EnquiryStatus::CustomerReplied,
            'last_customer_message_at' => $message->received_at,
            'read_at' => null,
        ])->save();
    }

    private function storeUnmatched(string $externalId, string $from, string $to, string $subject, string $reason, ?string $body): void
    {
        UnmatchedInboundMessage::create([
            'external_id' => $externalId,
            'from_email' => $this->emailAddress($from) ?? $from,
            'to_email' => $to,
            'subject' => $subject,
            'body' => $body,
            'reason' => $reason,
            'received_at' => now(),
        ]);
    }

    /**
     * Persist any attachments to the public disk.
     *
     * @return list<array{filename: string, path: string, content_type: string}>
     */
    private function storeAttachments(InboundEmail $email): array
    {
        $stored = [];

        foreach ($email->attachments as $attachment) {
            $content = $attachment['content'];
            if ($content === '') {
                continue;
            }

            $name = Str::slug(pathinfo($attachment['filename'], PATHINFO_FILENAME)) ?: 'file';
            $ext = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
            $path = 'enquiry-attachments/'.Str::random(20).'-'.$name.($ext !== '' ? '.'.$ext : '');

            Storage::disk('public')->put($path, base64_decode($content, true) ?: '');

            $stored[] = [
                'filename' => $attachment['filename'],
                'path' => $path,
                'content_type' => $attachment['content_type'],
            ];
        }

        return $stored;
    }

    /** Resend `to` can be a string or a list of strings. */
    private function firstAddress(mixed $to): string
    {
        if (is_string($to)) {
            return $to;
        }

        if (is_array($to) && isset($to[0])) {
            return is_string($to[0]) ? $to[0] : (string) ($to[0]['email'] ?? '');
        }

        return '';
    }

    /** Pull the bare address out of a "Name <email>" string. */
    private function emailAddress(string $value): ?string
    {
        if (preg_match('/[<\s]?([^<>\s]+@[^<>\s]+)>?/', $value, $m) === 1) {
            return trim($m[1], '<>');
        }

        return null;
    }
}
