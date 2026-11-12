/**
 * UI checks for the searchable "Assign to lawyer" picker on the admin enquiry page (desktop + iPhone).
 *   php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder && php artisan serve
 *   (needs an approved "Adv. Priya Sharma" and at least one enquiry, as tests/Browser/e2e.cjs does)
 *   BASE_URL=http://127.0.0.1:8000 ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD=... node tests/Browser/lawyer-picker.cjs
 * Screenshots are written to SHOTS_DIR (default ./storage/e2e).
 */
const { chromium, devices } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'Admin@12345';
const OUT = process.env.SHOTS_DIR || path.join(__dirname, '../../storage/e2e');
fs.mkdirSync(OUT, { recursive: true });
let pass = 0, fail = 0;
const check = (name, ok, extra = '') => { ok ? pass++ : fail++; console.log(`${ok ? 'PASS' : 'FAIL'} ${name} ${extra}`); };

async function login(page) {
  await page.goto(BASE + '/login');
  await page.fill('#f_email', ADMIN_EMAIL);
  await page.fill('#f_password', ADMIN_PASSWORD);
  await Promise.all([page.waitForNavigation(), page.click('form button[type=submit], form button.btn-primary')]);
}

async function run(label, ctxOpts, enquiryId, otherCode) {
  const browser = await chromium.launch();
  const ctx = await browser.newContext(ctxOpts);
  const page = await ctx.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(String(e)));
  page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
  let lookups = 0;
  page.on('request', r => { if (r.url().includes('/admin/lawyers/lookup')) lookups++; });
  await login(page);
  const url = `${BASE}/admin/enquiries/${enquiryId}`;
  await page.goto(url);
  const input = page.locator('#f_assigned_code');
  const hidden = page.locator('input[type=hidden][name=assigned_code]');
  const list = page.locator('#f_assigned_code_list');
  const P = `[${label}]`;

  check(`${P} initial value shows requested lawyer name + ID`, /· MLN-[A-Z0-9]{6,7}$/.test(await input.inputValue()), await input.inputValue());
  const initialCode = await hidden.inputValue();
  check(`${P} hidden field carries the profile ID`, /^MLN-[A-Z0-9]{6,7}$/.test(initialCode), initialCode);
  check(`${P} visible input has no name (only hidden field submits)`, (await input.getAttribute('name')) === null);
  check(`${P} combobox ARIA`, (await input.getAttribute('role')) === 'combobox' && (await input.getAttribute('aria-expanded')) === 'false' && (await input.getAttribute('aria-controls')) === 'f_assigned_code_list');
  check(`${P} labelled`, await page.locator('label[for=f_assigned_code]').innerText() === 'Assign to lawyer');
  if (label === 'iphone') {
    const fs = await input.evaluate(el => parseFloat(getComputedStyle(el).fontSize));
    check(`${P} input font >= 16px (no iOS zoom)`, fs >= 16, String(fs));
  }

  // Click shows a list of lawyers (dropdown behaviour) without typing.
  await input.click();
  await list.locator('[role=option]').first().waitFor();
  check(`${P} click opens list`, (await input.getAttribute('aria-expanded')) === 'true' && await list.locator('[role=option]').count() > 1);
  check(`${P} current lawyer marked selected`, await list.locator('[role=option][aria-selected=true]').count() === 1);
  await page.keyboard.press('Escape');
  check(`${P} Escape closes`, (await input.getAttribute('aria-expanded')) === 'false');

  // Type a name: debounced server search, keyboard select.
  lookups = 0;
  await input.fill('');
  await input.pressSequentially('priya sharma', { delay: 40 });
  await page.waitForTimeout(600);
  check(`${P} debounced: few requests while typing`, lookups <= 3, `requests=${lookups}`);
  const opts = list.locator('[role=option]');
  check(`${P} name search finds Priya`, await opts.count() >= 1 && await opts.count() <= 2 && (await opts.first().innerText()).includes('Adv. Priya Sharma'));
  check(`${P} option shows city / practice area line`, (await opts.first().innerText()).split('\n').length >= 3, JSON.stringify(await opts.first().innerText()));
  check(`${P} first match is active`, (await input.getAttribute('aria-activedescendant')) === 'f_assigned_code_opt_0');
  if (label === 'desktop') await page.screenshot({ path: `${OUT}/desktop-search-open.png` });
  else await page.screenshot({ path: `${OUT}/iphone-search-open.png` });
  await page.keyboard.press('Enter');
  const priyaCode = await hidden.inputValue();
  check(`${P} Enter selects without submitting`, page.url() === url && /^MLN-/.test(priyaCode) && (await input.inputValue()) === `Adv. Priya Sharma · ${priyaCode}`);
  await Promise.all([page.waitForNavigation(), page.click('[data-testid=enquiry-update] button.btn-primary')]);
  check(`${P} save assigns Priya`, (await page.content()).includes('Currently: Adv. Priya Sharma') && (await hidden.inputValue()) === priyaCode);

  // Search by profile ID and pick with mouse/tap.
  await input.fill('');
  await input.pressSequentially(otherCode.toLowerCase().slice(0, 7), { delay: 30 });
  await page.waitForTimeout(500);
  const hit = list.locator('[role=option]', { hasText: otherCode });
  check(`${P} profile ID prefix search`, await hit.count() === 1);
  await hit.click();
  check(`${P} click/tap selects`, (await hidden.inputValue()) === otherCode && (await input.getAttribute('aria-expanded')) === 'false');

  // Escape during an unfinished search restores the previous choice.
  await input.fill('Kum');
  await page.waitForTimeout(400);
  await page.keyboard.press('Escape');
  check(`${P} Escape restores previous choice`, (await hidden.inputValue()) === otherCode && (await input.inputValue()).endsWith(otherCode));

  // Arrow navigation with wrap-around.
  await input.fill('');
  await page.waitForTimeout(500);
  await list.locator('[role=option]').first().waitFor();
  const n = await list.locator('[role=option]').count();
  await page.keyboard.press('ArrowUp');
  const ad = await input.getAttribute('aria-activedescendant');
  check(`${P} ArrowUp wraps to last`, ad === `f_assigned_code_opt_${n - 1}`, `${ad} of ${n}`);
  await page.keyboard.press('Home');
  check(`${P} Home goes to first`, (await input.getAttribute('aria-activedescendant')) === 'f_assigned_code_opt_0');
  const more = await page.locator('text=Type more to narrow down').isVisible();
  check(`${P} capped list says type more (50 approved lawyers)`, more && n >= 15 && n <= 16, `n=${n}`);
  if (label === 'iphone') {
    const box = await page.locator('[data-testid=lawyer-picker] [x-show=open]').first().boundingBox();
    const vw = page.viewportSize().width;
    check(`${P} dropdown fits viewport width`, box.x >= 0 && box.x + box.width <= vw, JSON.stringify(box));
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    check(`${P} no horizontal overflow`, !overflow);
    await page.screenshot({ path: `${OUT}/iphone-list-open.png` });
  } else {
    await page.screenshot({ path: `${OUT}/desktop-list-open.png` });
  }
  await page.keyboard.press('Escape');

  // Free text that matches nobody: no results message, submit blocked.
  await input.fill('zzzzqq');
  await page.waitForTimeout(500);
  check(`${P} no-match message`, await page.locator('[data-testid=lawyer-picker] p', { hasText: 'No approved lawyer matches “zzzzqq”' }).isVisible());
  await page.click('[data-testid=enquiry-update] button.btn-primary');
  await page.waitForTimeout(300);
  check(`${P} unmatched text blocks save`, page.url() === url && await page.locator('text=Pick a lawyer from the list').isVisible() && (await input.getAttribute('aria-invalid')) === 'true');
  const live = await page.locator('[data-testid=lawyer-picker] [role=status]').innerText();
  check(`${P} live region announces`, live.length > 0, JSON.stringify(live));

  // Typed profile ID (no pick) still saves, like the old text box.
  await input.fill(otherCode.toLowerCase());
  await page.keyboard.press('Escape');
  await Promise.all([page.waitForNavigation(), page.click('[data-testid=enquiry-update] button.btn-primary')]);
  check(`${P} typed profile ID saves`, (await hidden.inputValue()) === otherCode && (await page.content()).includes('Enquiry updated'));

  // Typed unknown ID shows the server error.
  await input.fill('MLN-QQQQQQ');
  await Promise.all([page.waitForNavigation(), page.locator('#f_admin_notes').click().then(() => page.click('[data-testid=enquiry-update] button.btn-primary'))]);
  check(`${P} unknown ID shows server error`, await page.locator('[data-testid=lawyer-picker] .form-error', { hasText: 'No approved lawyer found with that profile ID.' }).isVisible() && (await input.getAttribute('aria-invalid')) === 'true');

  // Clear button unassigns.
  await page.goto(url);
  await page.getByRole('button', { name: 'Clear assigned lawyer' }).click();
  check(`${P} clear empties field`, (await hidden.inputValue()) === '' && (await input.inputValue()) === '');
  await Promise.all([page.waitForNavigation(), page.click('[data-testid=enquiry-update] button.btn-primary')]);
  check(`${P} save with blank unassigns`, (await page.content()).includes('Leave blank if not assigned yet.'));

  // Tab from a typed search picks the highlighted match and moves on.
  await input.fill('');
  await input.pressSequentially('priya', { delay: 30 });
  await page.waitForTimeout(500);
  await page.keyboard.press('Tab');
  check(`${P} Tab accepts highlighted match`, (await hidden.inputValue()) === priyaCode);

  // Server failure (e.g. rate limit) shows a clear message, then recovers on reopen.
  await page.route('**/admin/lawyers/lookup*', r => r.fulfill({ status: 429, body: 'Too Many Attempts.' }), { times: 1 });
  await page.keyboard.press('Escape');
  await input.fill('');
  await input.pressSequentially('amit', { delay: 20 });
  await page.waitForTimeout(500);
  check(`${P} request failure shows message`, await page.locator('[data-testid=lawyer-picker] p.text-rose-600').isVisible() && !(await page.locator('[data-testid=lawyer-picker] p', { hasText: 'No approved lawyer matches' }).isVisible()));
  await page.keyboard.press('Escape');
  await input.click();
  await page.waitForTimeout(500);
  check(`${P} reopening retries`, !(await page.locator('[data-testid=lawyer-picker] p.text-rose-600').isVisible()) && await list.locator('[role=option]').count() > 0);
  const jsErr = errors.filter(e => !e.includes('429'));
  check(`${P} no JS errors`, jsErr.length === 0, jsErr.join(' | '));
  await page.goto(url);
  await page.screenshot({ path: `${OUT}/${label}-closed.png` });
  await browser.close();
}

(async () => {
  // Pick an enquiry and an approved lawyer (not Priya) to work with.
  const b = await chromium.launch(); const p = await b.newPage();
  await login(p);
  await p.goto(BASE + '/admin/enquiries');
  const href = await p.locator('a[href*="/admin/enquiries/"]').first().getAttribute('href');
  const enquiryId = href.match(/enquiries\/(\d+)/)[1];
  const all = await (await p.request.get(BASE + '/admin/lawyers/lookup?q=')).json();
  const otherCode = all.results.find(r => !r.name.includes('Priya')).code;
  await b.close();
  await run('desktop', { viewport: { width: 1366, height: 900 } }, enquiryId, otherCode);
  await run('iphone', { ...devices['iPhone 13'] }, enquiryId, otherCode);
  console.log(`\n${pass}/${pass + fail} picker checks passed`);
  process.exit(fail ? 1 : 0);
})();
