/**
 * pa11y-ci config - WCAG 2.1 AA guardrail for SAHO (axe engine).
 *
 * Runs against A11Y_BASE_URL (defaults to local DDEV). CI points it at the live
 * site on a schedule, because the build pipeline never boots Drupal - there is
 * no live URL to test inside a PR.
 *
 *   Local: npx pa11y-ci -c tests/a11y/pa11yci.config.cjs
 *   Prod:  A11Y_BASE_URL=https://sahistory.org.za npx pa11y-ci -c tests/a11y/pa11yci.config.cjs
 *
 * The per-URL thresholds are the current axe baseline (+ a small buffer for
 * dynamic blocks). They are a RATCHET: they freeze the legacy a11y backlog so it
 * cannot grow, and any NEW regression above the baseline fails the run. Lower
 * them as legacy issues are fixed. They are not an endorsement of the backlog.
 */
const base = process.env.A11Y_BASE_URL || 'https://sahistory-web.ddev.site';

const targets = [
  { path: '/', threshold: 55 },
  { path: '/politics-society', threshold: 20 },
  { path: '/biographies', threshold: 21 },
  { path: '/people/dr-abdullah-abdurahman', threshold: 35 },
  { path: '/article/cradle-humankind', threshold: 28 },
  { path: '/node/37872', threshold: 18 },
];

module.exports = {
  defaults: {
    standard: 'WCAG2AA',
    runners: ['axe'],
    timeout: 60000,
    chromeLaunchConfig: {
      ignoreHTTPSErrors: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
  },
  urls: targets.map((t) => ({ url: `${base}${t.path}`, threshold: t.threshold })),
};
