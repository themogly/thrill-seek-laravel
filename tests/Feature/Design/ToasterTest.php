<?php

namespace Tests\Feature\Design;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Toasts carry the "Message sent!" confirmations and the simple forms' errors.
 * They render in palette tokens that pass AA with their text (navy secondary,
 * 14.2:1) — never raw Tailwind colours (green-600 was 3.13:1) — and the stack is
 * a live region so a screen reader hears them.
 */
class ToasterTest extends TestCase
{
    public function test_toasts_use_palette_tokens_that_pass_contrast(): void
    {
        $html = Blade::render('<x-ui.toaster />');

        $this->assertDoesNotMatchRegularExpression('/\bbg-(green|red|blue)-\d{3}\b/', $html, 'Toasts must use palette tokens, not raw Tailwind colours.');
        $this->assertStringContainsString('bg-secondary', $html);
        $this->assertStringContainsString('text-secondary-foreground', $html);
    }

    public function test_the_toast_stack_is_announced(): void
    {
        $html = Blade::render('<x-ui.toaster />');

        $this->assertMatchesRegularExpression('/role="status"[^>]*aria-live="polite"|aria-live="polite"[^>]*role="status"/', $html);
        $this->assertStringContainsString("t.type === 'error' ? 'alert'", $html);
    }
}
