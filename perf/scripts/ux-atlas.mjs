#!/usr/bin/env node
/**
 * Capture the UX-baseline screenshot matrix for a design-review round.
 *
 * Reads a matrix JSON (built by perf/scripts/ux-matrix.php) of
 * {slug, path, note} entries and captures each page full-page at a
 * mobile and a desktop viewport against the local DDEV site.
 *
 * Usage: node perf/scripts/ux-atlas.mjs <matrix.json> <out-dir> [base-url]
 */
import { chromium } from 'playwright';
import { mkdirSync, readFileSync, writeFileSync } from 'fs';

const [matrixFile, outDir, baseArg] = process.argv.slice(2);
if (!matrixFile || !outDir) {
  console.error('Usage: node perf/scripts/ux-atlas.mjs <matrix.json> <out-dir> [base-url]');
  process.exit(1);
}
const BASE = (baseArg || 'https://sahistory-web.ddev.site').replace(/\/$/, '');
const entries = JSON.parse(readFileSync(matrixFile, 'utf8'));
mkdirSync(outDir, { recursive: true });

const VIEWPORTS = {
  mobile: { width: 390, height: 844, dpr: 2 },
  desktop: { width: 1440, height: 900, dpr: 1 },
};

const browser = await chromium.launch({
  executablePath:
    process.env.CHROMIUM_PATH ||
    '/home/mno/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome',
});
const results = [];
try {
  for (const [vname, v] of Object.entries(VIEWPORTS)) {
    const ctx = await browser.newContext({
      viewport: { width: v.width, height: v.height },
      deviceScaleFactor: v.dpr,
      ignoreHTTPSErrors: true,
      userAgent:
        vname === 'mobile'
          ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
          : undefined,
    });
    for (const entry of entries) {
      const page = await ctx.newPage();
      const url = BASE + entry.path;
      const file = `${outDir}/${entry.slug}--${vname}.png`;
      let status = null;
      try {
        let resp;
        try {
          resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 });
        } catch (e) {
          resp = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
        }
        status = resp ? resp.status() : null;
        await page.waitForTimeout(500);
        try {
          await page.screenshot({ path: file, fullPage: true, animations: 'disabled' });
        } catch (err) {
          // Very tall pages can overflow the protocol; keep the viewport shot.
          await page.screenshot({ path: file, fullPage: false, animations: 'disabled' });
          status = `${status} (viewport-only)`;
        }
        console.log(`  ok ${entry.slug}--${vname} [${status}]`);
        results.push({ slug: entry.slug, viewport: vname, url, status: String(status), file });
      } catch (err) {
        console.log(`  FAIL ${entry.slug}--${vname} - ${err.message.split('\n')[0]}`);
        results.push({ slug: entry.slug, viewport: vname, url, status: 'FAILED: ' + err.message.split('\n')[0], file: null });
      }
      await page.close();
    }
    await ctx.close();
  }
} finally {
  await browser.close();
}
writeFileSync(`${outDir}/capture-log.json`, JSON.stringify(results, null, 2));
console.log(`\n${results.filter((r) => r.file).length}/${results.length} captured -> ${outDir}`);
