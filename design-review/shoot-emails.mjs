#!/usr/bin/env node
/** Screenshot every email preview at /dev/mail/* into design-review/emails/. */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

mkdirSync('design-review/emails', { recursive: true });

const base = 'http://thrill-seek-laravel.test';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 700, height: 900 } });

await page.goto(`${base}/dev/mail`, { waitUntil: 'networkidle' });
const keys = await page.$$eval('li a', (links) => links.map((a) => a.textContent.trim()));

let failures = 0;

for (const key of keys) {
    const response = await page.goto(`${base}/dev/mail/${key}`, { waitUntil: 'networkidle' });
    if (!response.ok()) {
        console.error(`FAIL ${key}: HTTP ${response.status()}`);
        failures++;
        continue;
    }
    await page.screenshot({ path: `design-review/emails/${key}.png`, fullPage: true });
    console.log(`ok: ${key}`);
}

await browser.close();
process.exit(failures ? 2 : 0);
