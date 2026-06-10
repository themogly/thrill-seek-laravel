#!/usr/bin/env node
/**
 * Screenshot helper for the design-review loop.
 *
 * Usage:
 *   node design-review/shoot.mjs <phase> <name> <path> [widths]
 *
 *   phase   before | after
 *   name    file prefix, e.g. "home"
 *   path    URL path on http://thrill-seek-laravel.test, e.g. "/"
 *   widths  comma list, default "1440,390"
 *
 * Saves design-review/<phase>/<name>-<width>.png (full page) and prints any
 * console errors / failed requests so each pass doubles as a JS health check.
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const [phase, name, path, widthsArg] = process.argv.slice(2);

if (!phase || !name || !path) {
    console.error('usage: shoot.mjs <before|after> <name> <path> [widths]');
    process.exit(1);
}

const widths = (widthsArg ?? '1440,390').split(',').map(Number);
const base = 'http://thrill-seek-laravel.test';
const dir = `design-review/${phase}`;
mkdirSync(dir, { recursive: true });

const browser = await chromium.launch();
const errors = [];

for (const width of widths) {
    const page = await browser.newPage({ viewport: { width, height: width < 500 ? 844 : 900 } });
    page.on('console', (msg) => {
        if (msg.type() === 'error') errors.push(`[${width}px console] ${msg.text()}`);
    });
    page.on('requestfailed', (req) => {
        errors.push(`[${width}px request] ${req.url()} — ${req.failure()?.errorText}`);
    });

    await page.goto(base + path, { waitUntil: 'networkidle' });
    await page.waitForTimeout(700); // let entrance animations settle
    await page.screenshot({ path: `${dir}/${name}-${width}.png`, fullPage: true });
    // Viewport-sized hero shot for detail inspection.
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
