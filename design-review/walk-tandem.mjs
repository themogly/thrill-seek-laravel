#!/usr/bin/env node
/** Walk the tandem booking flow like a customer (mobile viewport) and screenshot each step. */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

mkdirSync('design-review/after', { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
const errors = [];
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
page.on('pageerror', (e) => errors.push(String(e)));

await page.goto('http://thrill-seek-laravel.test/book/tandem', { waitUntil: 'networkidle' });

// Step 1 → choose the first slot
await page.locator('button:has-text("places left")').first().click();
await page.waitForTimeout(800);
await page.screenshot({ path: 'design-review/after/book-tandem-step2-390.png', fullPage: true });

// Step 2 → fill details
await page.fill('#bt-name', 'Jess Jumper');
await page.fill('#bt-email', 'jess@example.com');
await page.fill('#bt-phone', '07700900123');
await page.fill('#bt-dob', '1995-05-01');
await page.fill('#bt-weight', '80');
await page.fill('#bt-ecn', 'Pat Carer');
await page.fill('#bt-ecp', '07700900456');
// tick the first add-on if present
const addOn = page.locator('input[type=checkbox][value]').first();
if (await addOn.count()) await addOn.check();
await page.waitForTimeout(400);
await page.locator('button:has-text("Review booking")').click();
await page.waitForTimeout(800);
await page.screenshot({ path: 'design-review/after/book-tandem-step3-390.png', fullPage: true });

// Validation check: missing terms should show inline error
await page.locator('button:has-text("securely with Stripe")').click();
await page.waitForTimeout(800);
await page.screenshot({ path: 'design-review/after/book-tandem-step3-error-390.png', fullPage: true });

console.log(errors.length ? 'CONSOLE ERRORS:\n' + errors.join('\n') : 'walk ok, no console errors');
await browser.close();
process.exit(errors.length ? 2 : 0);
