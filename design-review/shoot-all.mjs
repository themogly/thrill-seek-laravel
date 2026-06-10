#!/usr/bin/env node
/** Shoot every public page in one phase: node design-review/shoot-all.mjs <before|after> */
import { execFileSync } from 'node:child_process';

const phase = process.argv[2] ?? 'before';

const pages = [
    ['home', '/'],
    ['tandem', '/tandem'],
    ['aff', '/aff'],
    ['coached', '/coached'],
    ['shop', '/shop'],
    ['testimonials', '/testimonials'],
    ['hall-of-fame', '/hall-of-fame'],
    ['contact', '/contact'],
    ['privacy', '/privacy'],
    ['terms', '/terms'],
    ['payment-success', '/payment/success'],
    ['payment-cancelled', '/payment/cancelled'],
    ['404', '/this-page-does-not-exist'],
];

for (const [name, path] of pages) {
    try {
        const out = execFileSync('node', ['design-review/shoot.mjs', phase, name, path], { encoding: 'utf8' });
        process.stdout.write(out);
    } catch (e) {
        process.stdout.write(`PROBLEMS on ${name}:\n${e.stdout ?? ''}${e.stderr ?? ''}\n`);
    }
}
