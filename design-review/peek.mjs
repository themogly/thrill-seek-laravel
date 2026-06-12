#!/usr/bin/env node
/**
 * Viewport-sized section capture (full-page shots blow past review limits).
 *   node design-review/peek.mjs <path> <width> <scrollY> <outfile> [selector]
 * With a selector, scrolls it into view instead of using scrollY.
 */
import { chromium } from 'playwright';

const [path, widthArg, scrollArg, out, selector] = process.argv.slice(2);
const width = Number(widthArg ?? 1440);
const height = width < 500 ? 844 : 900;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width, height }, reducedMotion: 'reduce' });
const errors = [];
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));

await page.goto('http://thrill-seek-laravel.test' + path, { waitUntil: 'networkidle' });
if (selector) {
    await page.locator(selector).first().scrollIntoViewIfNeeded();
} else if (Number(scrollArg)) {
    await page.evaluate((y) => window.scrollTo(0, y), Number(scrollArg));
}
await page.waitForTimeout(400);
await page.screenshot({ path: out });
await browser.close();

if (errors.length) {
    console.error('CONSOLE ERRORS:\n' + errors.join('\n'));
    process.exit(2);
}
console.log('ok: ' + out);
