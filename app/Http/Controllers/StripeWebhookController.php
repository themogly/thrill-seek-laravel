<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\HandleStripeWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, HandleStripeWebhook $handler): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            abort(400, 'Invalid webhook signature.');
        }

        $handler->handle($event);

        return response()->noContent();
    }
}
