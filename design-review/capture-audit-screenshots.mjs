import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const BASE_URL = 'http://thrill-seek-laravel.test';
const VIEWPORTS = [
  { name: '1440x900', width: 1440, height: 900 },
  { name: '1280x800', width: 1280, height: 800 },
  { name: '1024x800', width: 1024, height: 800 },
  { name: '1366x700', width: 1366, height: 700 },
  { name: '390x844', width: 390, height: 844 }
];

const PAGES = [
  { name: 'home', path: '/' },
  { name: 'tandem', path: '/tandem' },
  { name: 'aff', path: '/aff' },
  { name: 'coached', path: '/coached' }
];

const SCROLL_DELAY = 500;

async function capturePageAtViewport(page, pageName, pagePath, viewport) {
  try {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto(`${BASE_URL}${pagePath}`, { waitUntil: 'networkidle' });
    
    // Get page height to determine scroll sections
    const bodyHeight = await page.evaluate(() => document.body.scrollHeight);
    const viewportHeight = viewport.height;
    const numSections = Math.ceil(bodyHeight / viewportHeight);
    
    // Capture viewport-sized screenshots by scrolling
    for (let i = 0; i < numSections; i++) {
      const scrollPos = i * viewportHeight;
      await page.evaluate(pos => window.scrollTo(0, pos), scrollPos);
      await page.waitForTimeout(SCROLL_DELAY);
      
      const screenshotPath = `/Users/benhawker/Sites/thrill-seek-laravel/design-review/audit/${pageName}_${viewport.name}_section${i}.png`;
      await page.screenshot({ path: screenshotPath });
      console.log(`✓ ${pageName} @ ${viewport.name} section ${i}`);
    }
  } catch (error) {
    console.error(`✗ Failed to capture ${pageName} @ ${viewport.name}:`, error.message);
  }
}

async function runAudit() {
  const browser = await chromium.launch();
  
  try {
    // Ensure audit directory exists
    if (!fs.existsSync('/Users/benhawker/Sites/thrill-seek-laravel/design-review/audit')) {
      fs.mkdirSync('/Users/benhawker/Sites/thrill-seek-laravel/design-review/audit', { recursive: true });
    }
    
    for (const pageInfo of PAGES) {
      console.log(`\nCapturing ${pageInfo.name}...`);
      for (const viewport of VIEWPORTS) {
        const page = await browser.newPage();
        await capturePageAtViewport(page, pageInfo.name, pageInfo.path, viewport);
        await page.close();
      }
    }
    
    console.log('\n✓ All screenshots captured!');
  } finally {
    await browser.close();
  }
}

runAudit();
