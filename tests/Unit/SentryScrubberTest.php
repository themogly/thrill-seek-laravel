<?php

namespace Tests\Unit;

use App\Support\SentryScrubber;
use PHPUnit\Framework\TestCase;
use Sentry\Event;

class SentryScrubberTest extends TestCase
{
    public function test_it_drops_the_request_body_and_redacts_personal_keys(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'https://g-force.test/book/tandem',
            'method' => 'POST',
            'query_string' => 'email=jess%40example.com&ref=ABC',
            'cookies' => ['session' => 'secret-session-id'],
            'data' => [
                'name' => 'Jess Jumper',
                'email' => 'jess@example.com',
                'medical_notes' => 'mild asthma',
                'weight_kg' => '80',
                'date_of_birth' => '1990-01-01',
            ],
        ]);

        $scrubbed = SentryScrubber::scrub($event);
        $this->assertNotNull($scrubbed);
        $request = $scrubbed->getRequest();

        // The whole body is gone, cookies stripped, url/method kept.
        $this->assertArrayNotHasKey('data', $request);
        $this->assertNotContains('secret-session-id', (array) $request['cookies']);
        $this->assertSame('POST', $request['method']);

        // PII in the query string is redacted, non-PII kept.
        $this->assertStringNotContainsString('jess%40example.com', $request['query_string']);
        $this->assertStringNotContainsString('jess@example.com', urldecode($request['query_string']));
        $this->assertStringContainsString('ref=ABC', $request['query_string']);
    }

    public function test_it_recursively_redacts_personal_and_medical_keys_in_extra(): void
    {
        $event = Event::createEvent();
        $event->setExtra([
            'booking' => [
                'reference' => 'GF-123',          // safe — kept
                'customer_email' => 'jess@example.com',
                'medical_notes' => 'mild asthma',
                'price_pence' => 26000,            // safe — kept
            ],
            'stripe_payment_intent_id' => 'pi_123',
        ]);

        $extra = SentryScrubber::scrub($event)->getExtra();

        $this->assertSame('GF-123', $extra['booking']['reference']);
        $this->assertSame(26000, $extra['booking']['price_pence']);
        $this->assertSame('[redacted]', $extra['booking']['customer_email']);
        $this->assertSame('[redacted]', $extra['booking']['medical_notes']);
        $this->assertSame('[redacted]', $extra['stripe_payment_intent_id']);
    }
}
