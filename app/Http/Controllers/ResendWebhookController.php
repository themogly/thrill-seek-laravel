<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundEmail;
use App\Support\Inbound\ResendWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Inbound-email webhook — sibling of StripeWebhookController. Verifies the Svix
 * signature only, then hands the verified payload to a queued job so the heavy work
 * (the second Resend fetch) happens off the request and the endpoint returns fast.
 */
class ResendWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $valid = ResendWebhookSignature::isValid(
            $request->getContent(),
            $request->header('svix-id', ''),
            $request->header('svix-timestamp', ''),
            $request->header('svix-signature', ''),
            (string) config('services.resend.webhook_secret'),
        );

        abort_unless($valid, 400, 'Invalid webhook signature.');

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        ProcessInboundEmail::dispatch($payload);

        return response()->noContent();
    }
}
