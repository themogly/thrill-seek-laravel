#!/usr/bin/env node
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const [phase, name, path, widthsArg] = process.argv.slice(2);

if (!phase || !name || !path) {
    console.error('usage: shoot-authenticated.mjs <before|after> <name> <path> [widths]');
    process.exit(1);
}

const widths = (widthsArg ?? '1440,390').split(',').map(Number);
const base = 'http://thrill-seek-laravel.test';
const dir = `design-review/${phase}`;
mkdirSync(dir, { recursive: true });

const browser = await chromium.launch();
const errors = [];

for (const width of widths) {
    const page = await browser.newPage({ viewport: { width, height: width < 500 ? 844 : 900 }, reducedMotion: 'reduce' });
    page.on('console', (msg) => {
        if (msg.type() === 'error') errors.push(`[${width}px console] ${msg.text()}`);
    });
    page.on('requestfailed', (req) => {
        errors.push(`[${width}px request] ${req.url()} — ${req.failure()?.errorText}`);
    });

    // First, navigate to the dev login shortcut
    await page.goto(base + '/dev/account-login', { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    
    // Then navigate to the target page (cookies from login are preserved)
    await page.goto(base + path, { waitUntil: 'networkidle' });
    await page.waitForTimeout(700);
    await page.screenshot({ path: `${dir}/${name}-${width}.png`, fullPage: true });
    await page.screenshot({ path: `${dir}/${name}-${width}-hero.png` });
    await page.close();
}

await browser.close();

if (errors.length) {
    console.error('PROBLEMS:');
    for (const e of errors) console.error('  ' + e);
    process.exit(2);
}

console.log(`ok: ${widths.map((w) => `${dir}/${name}-${w}.png`).join(', ')}`);
