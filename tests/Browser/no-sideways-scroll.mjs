#!/usr/bin/env node
/**
 * The browser half of NoSidewaysScrollTest (prompt 028).
 *
 * Usage: node tests/Browser/no-sideways-scroll.mjs <base-url> <width>x<height> <path> [<path> …]
 *
 * Loads each path at the given viewport and prints ONE JSON object to stdout:
 *   { "<path>": { "status": 200, "scrollWidth": 375, "clientWidth": 375, "culprit": null, "failedRequests": [] }, … }
 * `culprit` names the widest element whose right edge passes the viewport and
 * that isn't inside a horizontally scrolling container (an `overflow-x` region
 * is allowed to be wider than the screen; that's what it's for).
 * `failedRequests` lists same-origin requests that failed or returned 4xx/5xx — a page
 * measured without its stylesheet isn't the real layout.
 * The PHP test decides pass/fail; this script only measures.
 */
import { chromium } from 'playwright';

const [base, viewport, ...paths] = process.argv.slice(2);

if (!base || !viewport || paths.length === 0) {
    console.error('usage: no-sideways-scroll.mjs <base-url> <width>x<height> <path> [<path> …]');
    process.exit(2);
}

const [width, height] = viewport.split('x').map(Number);
const browser = await chromium.launch();
const results = {};

try {
    for (const path of paths) {
        // reducedMotion: the data-reveal entrance transforms are skipped, so the
        // measurement is the settled layout, not a mid-animation frame.
        const page = await browser.newPage({ viewport: { width, height }, reducedMotion: 'reduce' });
        const failedRequests = [];
        const sameOrigin = (url) => url.startsWith(base) && !url.endsWith('/favicon.ico');
        page.on('requestfailed', (request) => sameOrigin(request.url()) && failedRequests.push(request.url()));
        page.on('response', (r) => sameOrigin(r.url()) && r.status() >= 400 && r.url() !== base + path && failedRequests.push(`${r.url()} (${r.status()})`));
        const response = await page.goto(base + path, { waitUntil: 'load' });

        results[path] = await page.evaluate(() => {
            const root = document.documentElement;
            const viewportWidth = root.clientWidth;

            const insideScroller = (element) => {
                for (let parent = element.parentElement; parent && parent !== document.body; parent = parent.parentElement) {
                    const overflowX = getComputedStyle(parent).overflowX;
                    if (overflowX === 'auto' || overflowX === 'scroll' || overflowX === 'hidden' || overflowX === 'clip') {
                        return true;
                    }
                }

                return false;
            };

            const selector = (element) => {
                const id = element.id ? `#${element.id}` : '';
                const classes = typeof element.className === 'string' && element.className.trim()
                    ? '.' + element.className.trim().split(/\s+/).slice(0, 4).join('.')
                    : '';

                return element.tagName.toLowerCase() + id + classes;
            };

            const describe = (element) => {
                const trail = [selector(element)];
                for (let parent = element.parentElement; parent && parent !== document.body && trail.length < 3; parent = parent.parentElement) {
                    trail.unshift(selector(parent));
                }
                const box = element.getBoundingClientRect();
                const text = (element.innerText || '').trim().replace(/\s+/g, ' ').slice(0, 60);

                return `${trail.join(' > ')}: right edge ${Math.round(box.right)}px, ${Math.round(box.width)}px wide${text ? ` ("${text}")` : ''}`;
            };

            let culprit = null;
            let widest = 0;

            if (root.scrollWidth > viewportWidth) {
                for (const element of document.body.querySelectorAll('*')) {
                    const box = element.getBoundingClientRect();
                    if (box.right <= viewportWidth + 0.5 || box.width === 0 || insideScroller(element)) {
                        continue;
                    }
                    if (box.width > widest) {
                        widest = box.width;
                        culprit = describe(element);
                    }
                }
            }

            return { scrollWidth: root.scrollWidth, clientWidth: viewportWidth, culprit };
        });
        results[path].status = response?.status() ?? 0;
        results[path].failedRequests = failedRequests;

        await page.close();
    }
} finally {
    await browser.close();
}

process.stdout.write(JSON.stringify(results));
