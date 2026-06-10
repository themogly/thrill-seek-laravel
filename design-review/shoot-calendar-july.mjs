#!/usr/bin/env node
/** Log into the admin panel and screenshot the booking calendar. */
import { chromium } from 'playwright';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));

await page.goto('http://thrill-seek-laravel.test/admin/login', { waitUntil: 'networkidle' });
await page.fill('input[type=email]', 'test@example.com');
await page.fill('input[type=password]', 'password');
await page.click('button[type=submit]');
await page.waitForURL((url) => url.pathname.startsWith('/admin') && !url.pathname.includes('login'), { timeout: 15000 });

await page.goto('http://thrill-seek-laravel.test/admin/bookings/calendar?month=2026-07', { waitUntil: 'networkidle' });
await page.waitForTimeout(700);
await page.screenshot({ path: 'design-review/after/admin-calendar-july-1440.png', fullPage: true });

console.log(errors.length ? 'CONSOLE ERRORS:\n' + errors.join('\n') : 'calendar shot ok');
await browser.close();
process.exit(errors.length ? 2 : 0);
