import { chromium } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = 'http://thrill-seek-laravel.test';
const OUTPUT_DIR = './design-review/audit-2026-fullpage';

// Create output directory
if (!fs.existsSync(OUTPUT_DIR)) {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

const viewports = [
  { width: 1366, height: 700, name: '1366x700-short-laptop' },
  { width: 1024, height: 800, name: '1024x800' },
];

const pages = [
  { url: '/testimonials', name: 'testimonials' },
  { url: '/hall-of-fame', name: 'hall-of-fame' },
];

async function captureScreenshots() {
  const browser = await chromium.launch();
  
  for (const page of pages) {
    console.log(`\n=== Capturing full-page ${page.name} ===`);
    
    for (const viewport of viewports) {
      const browserPage = await browser.newPage({
        viewport: { width: viewport.width, height: viewport.height }
      });
      
      try {
        const url = `${BASE_URL}${page.url}`;
        console.log(`  → ${viewport.name} from ${url}`);
        await browserPage.goto(url, { waitUntil: 'networkidle' });
        
        const filename = `fullpage-${page.name}-${viewport.name}.png`;
        const filepath = path.join(OUTPUT_DIR, filename);
        
        // Capture full page
        await browserPage.screenshot({ path: filepath, fullPage: true });
        console.log(`     ✓ Saved: ${filepath}`);
      } catch (error) {
        console.error(`  ✗ Error: ${error.message}`);
      } finally {
        await browserPage.close();
      }
    }
  }
  
  await browser.close();
  console.log(`\n✅ All full-page screenshots captured to ${OUTPUT_DIR}`);
}

captureScreenshots().catch(console.error);
