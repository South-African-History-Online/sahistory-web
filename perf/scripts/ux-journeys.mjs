#!/usr/bin/env node
/**
 * Scripted user-journey walkthrough for the UX-baseline design-review bundle.
 *
 * Each journey performs real interactions (search, facets, citation modal,
 * classroom language switch, chronology scroll) and saves numbered
 * screenshots plus a step log for the UX narrative.
 *
 * Usage: node perf/scripts/ux-journeys.mjs <out-dir> [J1 J2 ...]
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'fs';

const outDir = process.argv[2];
const only = process.argv.slice(3);
if (!outDir) {
  console.error('Usage: node perf/scripts/ux-journeys.mjs <out-dir> [J1 J2 ...]');
  process.exit(1);
}
mkdirSync(outDir, { recursive: true });

const BASE = process.env.UX_BASE || 'https://sahistory-web.ddev.site';
const SHOP = process.env.UX_SHOP || 'https://shop.ddev.site';
const log = [];

const browser = await chromium.launch({
  executablePath:
    process.env.CHROMIUM_PATH ||
    '/home/mno/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome',
});

const makePage = async (viewport) => {
  const v = viewport === 'mobile'
    ? { width: 390, height: 844, dpr: 2 }
    : { width: 1440, height: 900, dpr: 1 };
  const ctx = await browser.newContext({
    viewport: { width: v.width, height: v.height },
    deviceScaleFactor: v.dpr,
    ignoreHTTPSErrors: true,
    userAgent: viewport === 'mobile'
      ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
      : undefined,
  });
  return { ctx, page: await ctx.newPage() };
};

let current = '';
const step = async (name, fn) => {
  try {
    const raw = await fn();
    const detail = typeof raw === 'string' ? raw : '';
    log.push({ journey: current, step: name, ok: true, detail });
    console.log(`  ok   ${name}${detail ? ' - ' + detail : ''}`);
  } catch (err) {
    const msg = err.message.split('\n')[0].slice(0, 200);
    log.push({ journey: current, step: name, ok: false, detail: msg });
    console.log(`  FAIL ${name} - ${msg}`);
  }
};

const shoot = (page, name, full = false) =>
  page.screenshot({ path: `${outDir}/${name}.png`, fullPage: full, timeout: 20000 })
    .catch(() => page.screenshot({ path: `${outDir}/${name}.png`, fullPage: false }));

const goto = async (page, path, base = BASE) => {
  try {
    await page.goto(base + path, { waitUntil: 'networkidle', timeout: 45000 });
  } catch {
    await page.goto(base + path, { waitUntil: 'domcontentloaded', timeout: 45000 });
  }
  await page.waitForTimeout(400);
};

const clickFirst = async (page, selectors, what) => {
  for (const sel of selectors) {
    const loc = page.locator(sel).first();
    if (await loc.count() && await loc.isVisible().catch(() => false)) {
      await loc.click();
      return sel;
    }
  }
  throw new Error(`no visible match for ${what}: ${selectors.join(' | ')}`);
};

const journeys = {

  // J1 - first visit: home, TDIH + birthday picker, chrome, search overlay.
  J1: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j1-01 home top (desktop)', async () => {
      await goto(page, '/');
      await shoot(page, 'j1-01-home-top--desktop');
    });
    await step('j1-02 home full (desktop)', () => shoot(page, 'j1-02-home-full--desktop', true));
    await step('j1-03 TDIH band', async () => {
      const tdih = page.locator('#tdih-day-month-form-wrapper, .tdih-events-container, [class*="tdih"]').first();
      await tdih.scrollIntoViewIfNeeded();
      await page.waitForTimeout(300);
      await shoot(page, 'j1-03-tdih-band--desktop');
    });
    await step('j1-04 birthday picker result', async () => {
      // The pickers enable in sequence: day -> month -> year.
      await page.locator('select[name="birthday_day"]').first().scrollIntoViewIfNeeded();
      await page.locator('select[name="birthday_day"]').first().selectOption({ index: 18 });
      await page.locator('select[name="birthday_month"]').first().selectOption({ index: 7 });
      await page.locator('select[name="birthday_year"]').first().selectOption({ index: 30 });
      await clickFirst(page, ['input[value="Show Events"]', '.tdih-birthday-submit'], 'tdih submit');
      await page.waitForTimeout(2500);
      await shoot(page, 'j1-04-tdih-birthday-result--desktop');
    });
    await step('j1-05 search overlay open + typed', async () => {
      const sel = await clickFirst(page, ['header button[aria-label*="search" i]', 'button[data-bs-target*="search" i]', '.saho-search-toggle', 'header a[href="/search"]', 'button:has-text("Search")'], 'search trigger');
      await page.waitForTimeout(600);
      await shoot(page, 'j1-05-search-overlay--desktop');
      const input = page.locator('input[type=search]:visible, input[name*="search" i]:visible, input[placeholder*="search" i]:visible').first();
      await input.fill('mandela');
      await page.waitForTimeout(800);
      await shoot(page, 'j1-06-search-overlay-typed--desktop');
      return `trigger=${sel}`;
    });
    await ctx.close();

    const m = await makePage('mobile');
    await step('j1-07 home top (mobile)', async () => {
      await goto(m.page, '/');
      await shoot(m.page, 'j1-07-home-top--mobile');
    });
    await step('j1-08 mobile menu open', async () => {
      const sel = await clickFirst(m.page, ['button.navbar-toggler', 'button[aria-label*="menu" i]', 'button[data-bs-toggle="offcanvas"]', 'header button[aria-expanded]'], 'hamburger');
      await m.page.waitForTimeout(700);
      await shoot(m.page, 'j1-08-mobile-menu--mobile');
      return `trigger=${sel}`;
    });
    await step('j1-09 footer (mobile)', async () => {
      await goto(m.page, '/');
      await m.page.locator('footer').first().scrollIntoViewIfNeeded();
      await m.page.waitForTimeout(400);
      await shoot(m.page, 'j1-09-footer--mobile');
    });
    await m.ctx.close();
  },

  // J2 - researcher: global search -> advanced -> archive record -> citation.
  J2: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j2-01 global search results', async () => {
      await goto(page, '/search?search_api_fulltext=mandela');
      await shoot(page, 'j2-01-search-results--desktop');
    });
    await step('j2-02 global search full', () => shoot(page, 'j2-02-search-results-full--desktop', true));
    await step('j2-03 advanced search', async () => {
      await goto(page, '/search/advanced');
      await shoot(page, 'j2-03-advanced-search--desktop', true);
    });
    await step('j2-04 archive record top', async () => {
      await goto(page, '/archive/say-it-out-loud-1939-presidential-address-cape-town-11th-april-1939');
      await shoot(page, 'j2-04-archive-record--desktop');
    });
    await step('j2-05 archive record full (PART OF, prev/next)', () => shoot(page, 'j2-05-archive-record-full--desktop', true));
    await step('j2-06 citation modal', async () => {
      const sel = await clickFirst(page, ['button.citation-button[data-citation-trigger]', '[data-citation-trigger]', 'button:has-text("Cite")'], 'cite trigger');
      await page.waitForTimeout(800);
      await shoot(page, 'j2-06-citation-modal--desktop');
      return `trigger=${sel}`;
    });
    await step('j2-07 citation second tab', async () => {
      const tabs = page.locator('.modal [role=tab], .modal .nav-link');
      if (await tabs.count() > 1) await tabs.nth(1).click();
      await page.waitForTimeout(400);
      await shoot(page, 'j2-07-citation-tab2--desktop');
    });
    await ctx.close();
  },

  // J3 - archive browse: rail facets, keyword, sort. Desktop + mobile.
  J3: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j3-01 /archives default', async () => {
      await goto(page, '/archives');
      await shoot(page, 'j3-01-archives--desktop');
    });
    await step('j3-02 keyword search', async () => {
      const input = page.locator('input[name="combine"]').first();
      await input.fill('freedom charter');
      await input.press('Enter');
      await page.waitForTimeout(2500);
      await shoot(page, 'j3-02-archives-keyword--desktop');
    });
    await step('j3-03 facet applied', async () => {
      const box = page.locator('form input[type=checkbox]:visible').first();
      await box.check();
      await page.waitForTimeout(2500);
      await shoot(page, 'j3-03-archives-facet--desktop');
    });
    await step('j3-04 sort changed', async () => {
      const sort = page.locator('select[data-saho-archive-sort], select[name="sort_by"]:visible').first();
      await sort.selectOption({ index: 1 });
      await page.waitForTimeout(2500);
      await shoot(page, 'j3-04-archives-sorted--desktop');
    });
    await step('j3-05 collection narrowed (DISA)', async () => {
      await goto(page, '/archives?collection=100309');
      await shoot(page, 'j3-05-archives-disa--desktop');
    });
    await ctx.close();
    const m = await makePage('mobile');
    await step('j3-06 /archives (mobile)', async () => {
      await goto(m.page, '/archives');
      await shoot(m.page, 'j3-06-archives--mobile', true);
    });
    await m.ctx.close();
  },

  // J4 - biography: landing -> filtered -> record with related tabs.
  J4: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j4-01 /biographies', async () => {
      await goto(page, '/biographies');
      await shoot(page, 'j4-01-biographies--desktop');
    });
    await step('j4-02 biography record', async () => {
      await goto(page, '/people/teboho-tsietsi-mashinini');
      await shoot(page, 'j4-02-biography-top--desktop');
    });
    await step('j4-03 biography full', () => shoot(page, 'j4-03-biography-full--desktop', true));
    await step('j4-04 related tab clicked', async () => {
      const tabs = page.locator('[role=tab]:visible, .nav-tabs .nav-link:visible');
      if (await tabs.count() > 1) {
        await tabs.nth(1).click();
        await page.waitForTimeout(600);
      }
      await shoot(page, 'j4-04-biography-related-tab--desktop');
      return `${await tabs.count()} tabs`;
    });
    await ctx.close();
  },

  // J5 - learner: classroom -> grade -> deck -> language switch -> origin link.
  J5: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j5-01 /classroom', async () => {
      await goto(page, '/classroom');
      await shoot(page, 'j5-01-classroom--desktop', true);
    });
    await step('j5-02 deck top (language bar)', async () => {
      await goto(page, '/classroom/grade-5/heritage-trail-through-provinces-south-africa');
      await shoot(page, 'j5-02-deck-top--desktop');
    });
    await step('j5-03 deck full', () => shoot(page, 'j5-03-deck-full--desktop', true));
    await step('j5-04 language switched', async () => {
      const item = page.locator('.saho-translation-switcher__item a, .saho-translation-switcher a').nth(2);
      await item.click();
      await page.waitForTimeout(1200);
      await shoot(page, 'j5-04-deck-translated--desktop');
      return page.url();
    });
    await ctx.close();
    const m = await makePage('mobile');
    await step('j5-05 deck (mobile)', async () => {
      await goto(m.page, '/classroom/grade-5/heritage-trail-through-provinces-south-africa');
      await shoot(m.page, 'j5-05-deck--mobile', true);
    });
    await m.ctx.close();
  },

  // J6 - time traveller: timelines register, chronology scrollspy, Svelte app.
  J6: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j6-01 /timelines', async () => {
      await goto(page, '/timelines');
      await shoot(page, 'j6-01-timelines--desktop', true);
    });
    await step('j6-02 chronology article top', async () => {
      await goto(page, '/article/1946-mine-workers-strike-timeline-1867-1987');
      await shoot(page, 'j6-02-chronology-top--desktop');
    });
    await step('j6-03 chronology mid-scroll (sticky decade index)', async () => {
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.4));
      await page.waitForTimeout(800);
      await shoot(page, 'j6-03-chronology-scrolled--desktop');
    });
    await step('j6-04 svelte timeline app', async () => {
      await goto(page, '/timeline');
      await page.waitForTimeout(4000);
      // Continuous canvas animation defeats the default screenshot stability wait.
      await page.screenshot({ path: `${outDir}/j6-04-timeline-app--desktop.png`, animations: 'disabled', caret: 'hide', timeout: 20000 });
    });
    await ctx.close();
  },

  // J7 - visual: history through pictures, africa, gallery node.
  J7: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j7-01 history-through-pictures', async () => {
      await goto(page, '/history-through-pictures');
      await shoot(page, 'j7-01-htp--desktop', true);
    });
    await step('j7-02 africa landing', async () => {
      await goto(page, '/africa');
      await shoot(page, 'j7-02-africa--desktop', true);
    });
    await step('j7-03 gallery node', async () => {
      await goto(page, '/node/153314');
      await shoot(page, 'j7-03-gallery--desktop', true);
    });
    await ctx.close();
  },

  // J8 - supporter: donate, champions, shop chrome peek.
  J8: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j8-01 /donate', async () => {
      await goto(page, '/donate');
      await shoot(page, 'j8-01-donate--desktop', true);
    });
    await step('j8-02 /champion wall', async () => {
      await goto(page, '/champion');
      await shoot(page, 'j8-02-champions--desktop', true);
    });
    await step('j8-03 shop landing', async () => {
      await goto(page, '/', SHOP);
      await shoot(page, 'j8-03-shop-home--desktop');
    });
    await step('j8-04 shop product + cart', async () => {
      const link = page.locator('a[href*="/product/"]').first();
      await link.click();
      await page.waitForTimeout(1500);
      await shoot(page, 'j8-04-shop-product--desktop');
      await clickFirst(page, ['button:has-text("Add to cart")', 'input[value="Add to cart"]'], 'add to cart');
      await page.waitForTimeout(2000);
      await shoot(page, 'j8-05-shop-cart--desktop');
    });
    await ctx.close();
  },

  // J9 - editorial: /featured register with chips + sort. Desktop + mobile.
  J9: async () => {
    const { ctx, page } = await makePage('desktop');
    await step('j9-01 /featured full', async () => {
      await goto(page, '/featured');
      await shoot(page, 'j9-01-featured-full--desktop', true);
    });
    await step('j9-02 section chip', async () => {
      const chip = page.locator('a[href*="section="], a[href*="?section"]').first();
      await chip.click();
      await page.waitForTimeout(1500);
      await shoot(page, 'j9-02-featured-chip--desktop');
      return page.url();
    });
    await ctx.close();
    const m = await makePage('mobile');
    await step('j9-03 /featured (mobile)', async () => {
      await goto(m.page, '/featured');
      await shoot(m.page, 'j9-03-featured--mobile', true);
    });
    await m.ctx.close();
  },
};

try {
  for (const [name, fn] of Object.entries(journeys)) {
    if (only.length && !only.includes(name)) continue;
    current = name;
    console.log(`\n== ${name}`);
    await fn();
  }
} finally {
  await browser.close();
}
writeFileSync(`${outDir}/journey-log.json`, JSON.stringify(log, null, 2));
const fails = log.filter((s) => !s.ok).length;
console.log(`\n${log.length - fails}/${log.length} steps ok -> ${outDir}`);
