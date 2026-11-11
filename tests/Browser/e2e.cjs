/**
 * End-to-end UI tests (desktop + mobile) using Playwright.
 *   php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder && php artisan serve
 *   BASE_URL=http://127.0.0.1:8000 ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD=... node tests/Browser/e2e.cjs
 * Screenshots are written to SHOTS_DIR (default ./storage/e2e).
 */
const { chromium, devices } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'Admin@12345';
const SHOTS = process.env.SHOTS_DIR || path.join(__dirname, '../../storage/e2e');
fs.mkdirSync(SHOTS, { recursive: true });

const results = [];
async function step(name, fn) {
    try { await fn(); results.push(['PASS', name]); console.log('PASS', name); }
    catch (e) { results.push(['FAIL', name, e.message.split('\n')[0]]); console.log('FAIL', name, '\n   ', e.message.split('\n').slice(0, 3).join('\n    ')); }
}
function assert(cond, msg) { if (!cond) throw new Error(msg); }

async function newPage(browser, opts, label) {
    const ctx = await browser.newContext({ ...opts, baseURL: BASE });
    const page = await ctx.newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error') page.errors.push(m.text() + ' @ ' + page.url()); });
    page.on('response', (r) => { if (r.status() >= 400 && r.status() !== 422) page.errors.push(`HTTP ${r.status()} ${r.url()}`); });
    page.label = label;
    return { ctx, page };
}
async function noOverflow(page) {
    const o = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    assert(o <= 1, `horizontal overflow of ${o}px`);
}
async function acceptDisclaimer(page) {
    const btn = page.getByTestId('disclaimer-accept');
    if (await btn.isVisible().catch(() => false)) await btn.click();
}
async function shot(page, name) { await page.screenshot({ path: path.join(SHOTS, `${page.label}-${name}.png`), fullPage: true }); }

function makeFiles() {
    const dir = path.join(SHOTS, 'fixtures'); fs.mkdirSync(dir, { recursive: true });
    const pdf = path.join(dir, 'enrolment.pdf');
    fs.writeFileSync(pdf, '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 200 200]/Parent 2 0 R>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF');
    // 1x1 PNG
    const png = path.join(dir, 'photo.png');
    fs.writeFileSync(png, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64'));
    return { pdf, png };
}

(async () => {
    const browser = await chromium.launch();
    const files = makeFiles();
    const stamp = Date.now().toString().slice(-6);
    const lawyerEmail = `e2e.lawyer.${stamp}@example.com`;
    const lawyerName = `Adv. Etoe Tester ${stamp}`;

    for (const [label, opts] of [['desktop', { viewport: { width: 1366, height: 900 } }], ['mobile', { ...devices['iPhone 13'] }]]) {
        const { ctx, page } = await newPage(browser, opts, label);

        await step(`[${label}] "Read full disclaimer" opens a readable page; popup returns on Back`, async () => {
            const modal = page.getByTestId('disclaimer');
            const locked = () => page.evaluate(() => document.documentElement.classList.contains('overflow-hidden'));
            await page.goto('/');
            await modal.waitFor({ state: 'visible' });
            await modal.getByRole('link', { name: 'Read full disclaimer' }).click();
            await page.waitForURL(/\/legal\/disclaimer$/);
            await page.getByTestId('legal-content').waitFor();
            await page.waitForTimeout(300);
            assert(!(await modal.isVisible()), 'popup covers the full disclaimer');
            assert(!(await locked()), 'page scroll is locked on the full disclaimer');
            // The text is readable and on top: the last section heading can be scrolled to and is not covered.
            const last = page.getByTestId('legal-content').getByRole('heading', { name: /Limitation of liability/ });
            await last.scrollIntoViewIfNeeded();
            await last.click({ trial: true });
            assert((await page.evaluate(() => window.scrollY)) > 0, 'page did not scroll');
            await noOverflow(page);
            await shot(page, '01a-full-disclaimer');

            await page.goBack();
            await page.waitForURL((u) => u.pathname === '/');
            await modal.waitFor({ state: 'visible' });
            assert(await locked(), 'scroll not locked behind the popup after Back');

            await modal.getByRole('link', { name: 'Cookie Policy' }).click();
            await page.waitForURL(/\/legal\/cookies$/);
            await page.getByTestId('legal-content').waitFor();
            await page.waitForTimeout(300);
            assert(!(await modal.isVisible()), 'popup covers the cookie policy');

            await page.goBack();
            await page.waitForURL((u) => u.pathname === '/');
            await modal.waitFor({ state: 'visible' });

            await page.goto('/legal/terms');
            await modal.waitFor({ state: 'visible' });
        });

        await step(`[${label}] popup accepted on another page stays closed after Back`, async () => {
            await page.goto('/about');
            await page.getByTestId('disclaimer').waitFor({ state: 'visible' });
            await page.goto('/legal/terms');
            await page.getByTestId('disclaimer-accept').click();
            await page.getByTestId('disclaimer').waitFor({ state: 'hidden' });
            await page.goBack();
            await page.waitForURL(/\/about$/);
            await page.waitForTimeout(300);
            assert(!(await page.getByTestId('disclaimer').isVisible()), 'stale popup after Back');
            assert(!(await page.evaluate(() => document.documentElement.classList.contains('overflow-hidden'))), 'scroll still locked after Back');
            await page.context().clearCookies();
        });

        await step(`[${label}] disclaimer shows once, then is remembered`, async () => {
            await page.goto('/');
            await page.getByTestId('disclaimer').waitFor({ state: 'visible' });
            await shot(page, '01-home-disclaimer');
            await page.getByTestId('disclaimer-accept').click();
            await page.getByTestId('disclaimer').waitFor({ state: 'hidden' });
            await page.reload();
            await page.waitForTimeout(300);
            assert(!(await page.getByTestId('disclaimer').isVisible()), 'disclaimer re-appeared');
            await noOverflow(page);
            await shot(page, '02-home');
        });

        await step(`[${label}] hero search with dependent state→city dropdown`, async () => {
            await page.goto('/');
            await page.selectOption('#hero-state', 'maharashtra');
            await page.waitForFunction(() => document.querySelectorAll('#hero-city option').length > 5);
            await page.selectOption('#hero-city', 'mumbai');
            await page.getByTestId('hero-search').getByRole('button', { name: 'Search' }).click();
            await page.waitForURL(/state=maharashtra/);
            assert(page.url().includes('city=mumbai'), 'city not in URL: ' + page.url());
            const h = await page.getByTestId('results-heading').innerText();
            assert(h.includes('Mumbai, Maharashtra'), 'heading: ' + h);
            await noOverflow(page);
        });

        await step(`[${label}] filters apply, show chips and can be removed`, async () => {
            await page.goto('/lawyers');
            const total = parseInt((await page.getByTestId('results-count').innerText()).replace(/,/g, ''), 10);
            assert(total > 0, 'no results on unfiltered list');
            if (label === 'mobile') {
                await page.getByTestId('open-filters').click();
                await page.waitForTimeout(350);
                await shot(page, '04-filters-drawer');
            }
            await page.selectOption('#area', 'criminal-law');
            await page.selectOption('#experience', '5');
            await page.getByTestId('apply-filters').click();
            await page.waitForURL(/area=criminal-law/);
            assert(!/[?&](q|state|language|court|gender|fee_max)=(&|$)/.test(page.url()), 'empty params left in URL: ' + page.url());
            const chips = await page.getByTestId('active-filters').innerText();
            assert(chips.includes('Criminal Law') && chips.includes('5+ years'), 'chips: ' + chips);
            const filtered = parseInt((await page.getByTestId('results-count').innerText()).replace(/,/g, ''), 10);
            assert(filtered <= total, 'filtered count larger than total');
            await shot(page, '03-results-filtered');
            await page.getByRole('link', { name: /Remove filter Criminal Law/ }).click();
            await page.waitForURL((u) => !u.toString().includes('area='));
            await noOverflow(page);
        });

        await step(`[${label}] city dropdown keeps value after reload of filtered URL`, async () => {
            await page.goto('/lawyers?state=karnataka&city=bengaluru');
            await page.waitForTimeout(300);
            assert((await page.inputValue('#city')) === 'bengaluru', 'city select lost value');
            await page.selectOption('#state', 'goa');
            await page.waitForFunction(() => [...document.querySelectorAll('#city option')].some((o) => o.value === 'panaji'));
            assert((await page.inputValue('#city')) === '', 'city should reset when state changes');
        });

        await step(`[${label}] empty state for impossible search`, async () => {
            await page.goto('/lawyers?q=zzzznotalawyer');
            await page.getByTestId('empty-state').waitFor();
            await shot(page, '05-empty');
        });

        await step(`[${label}] profile hides private phone and offers company contact`, async () => {
            await page.goto('/lawyers?q=Priya+Sharma');
            await page.getByTestId('lawyer-card').first().click();
            await page.getByTestId('lawyer-name').waitFor();
            const html = await page.content();
            assert(!html.includes('9000011111'), 'PRIVATE PHONE LEAKED');
            const tel = await page.getByTestId('call-company').first().getAttribute('href');
            const wa = await page.getByTestId('whatsapp-company').first().getAttribute('href');
            assert(tel.startsWith('tel:'), 'tel link');
            const code = (await page.getByTestId('profile-id').innerText()).trim();
            assert(wa.startsWith('https://wa.me/') && decodeURIComponent(wa).includes(code), 'whatsapp link lacks profile id');
            await noOverflow(page);
            await shot(page, '06-profile');
        });

        await step(`[${label}] enquiry: client-side errors then successful callback request`, async () => {
            const form = page.getByTestId('enquiry-form');
            await form.getByLabel('Your name').fill('E2E Client');
            await form.getByLabel('Mobile number').fill('12345');
            await form.locator('textarea[name=message]').fill('I need advice regarding a cheque bounce notice I received.');
            await form.locator('input[name=consent]').check();
            await page.waitForTimeout(3200); // human-speed submission (anti-bot timer)
            await page.getByTestId('enquiry-submit').click();
            await page.waitForLoadState('networkidle');
            assert((await page.content()).includes('Enter a valid 10-digit Indian mobile number'), 'phone validation not shown');
            await page.getByTestId('enquiry-form').getByLabel('Mobile number').fill('98111 22233');
            await page.getByTestId('enquiry-form').locator('input[name=consent]').check();
            await page.waitForTimeout(3200);
            await page.getByTestId('enquiry-submit').click();
            await page.getByTestId('enquiry-success').waitFor();
            const txt = await page.getByTestId('enquiry-success').innerText();
            assert(/ENQ-[A-Z0-9]{7}/.test(txt), 'no reference: ' + txt);
            await shot(page, '07-enquiry-success');
        });

        if (label === 'mobile') {
            await step('[mobile] menu opens and links work', async () => {
                await page.goto('/');
                await page.getByRole('button', { name: 'Toggle menu' }).click();
                await page.locator('#mobile-menu').getByRole('link', { name: 'For Lawyers' }).click();
                await page.waitForURL(/for-lawyers/);
                await noOverflow(page);
            });
        }

        await step(`[${label}] legal pages and 404 render without overflow`, async () => {
            for (const p of ['terms', 'privacy', 'disclaimer', 'lawyer-terms', 'cookies', 'intellectual-property', 'grievance']) {
                const r = await page.goto('/legal/' + p);
                assert(r.status() === 200, p + ' status ' + r.status());
                await page.getByTestId('legal-content').waitFor();
                await noOverflow(page);
            }
            await shot(page, '08-legal-grievance');
        });

        await step(`[${label}] friendly 404 page`, async () => {
            const p404 = await ctx.newPage();
            const r = await p404.goto('/this-page-does-not-exist');
            assert(r.status() === 404, '404 expected');
            assert((await p404.content()).includes('Page not found'), '404 copy');
            await p404.close();
        });

        await step(`[${label}] no JavaScript errors`, async () => {
            assert(page.errors.length === 0, page.errors.join(' | '));
        });
        await ctx.close();
    }

    // ---- Full registration → approval → edit flow (desktop) ----
    const { ctx: lctx, page: lp } = await newPage(browser, { viewport: { width: 1280, height: 900 } }, 'flow');
    await lp.context().addCookies([{ name: 'mln_disclaimer', value: '1', url: BASE }]);

    await step('[flow] wizard blocks Continue until step is valid', async () => {
        await lp.goto('/register');
        await lp.getByTestId('wizard-next').click();
        assert(await lp.locator('[data-step="0"]').isVisible(), 'moved past invalid step');
    });

    await step('[flow] lawyer registers through the 5-step wizard', async () => {
        await lp.fill('#f_email', lawyerEmail);
        await lp.fill('#f_password', 'Secret123');
        await lp.fill('#f_password_confirmation', 'Secret123');
        await lp.getByTestId('wizard-next').click();
        await lp.fill('#f_full_name', lawyerName);
        await lp.selectOption('#f_gender', 'female');
        await lp.fill('#f_phone', '9876500011');
        await lp.getByTestId('wizard-next').click();
        await lp.fill('#f_enrolment_number', 'E2E/' + stamp + '/2014');
        await lp.fill('#f_bar_council', 'Bar Council of Delhi');
        await lp.selectOption('#f_practice_since', '2014');
        // practice areas required: Continue should be blocked first
        await lp.getByTestId('wizard-next').click();
        assert(await lp.locator('[data-step="2"]').isVisible(), 'moved without practice area');
        await lp.getByTestId('checklist-practice_areas').getByLabel('Criminal Law').check();
        await lp.getByTestId('checklist-practice_areas').getByLabel('Cyber Crime & IT').check();
        await lp.getByTestId('checklist-languages').getByLabel('English').check();
        await lp.getByTestId('checklist-courts').getByLabel('High Court').check();
        await shot(lp, '10-register-professional');
        await lp.getByTestId('wizard-next').click();
        await lp.selectOption('#f_state_id', { label: 'Delhi' });
        await lp.waitForFunction(() => document.querySelectorAll('#f_city_id option').length > 2);
        await lp.selectOption('#f_city_id', { label: 'New Delhi' });
        await lp.fill('#f_bio', 'Criminal defence and cyber crime matters before Delhi courts.');
        await lp.getByTestId('wizard-next').click();
        await lp.setInputFiles('#f_photo', files.png);
        await lp.setInputFiles('#f_enrolment_certificate', files.pdf);
        await lp.setInputFiles('#f_id_proof', files.pdf);
        await lp.check('input[name=accept_terms]');
        await lp.check('input[name=declaration]');
        await shot(lp, '11-register-documents');
        await lp.getByTestId('register-submit').click();
        await lp.waitForURL(/\/lawyer$/);
        assert((await lp.getByTestId('status-badge').innerText()).includes('Pending'), 'not pending');
        await shot(lp, '12-lawyer-dashboard-pending');
    });

    let slug = null;
    const { ctx: actx, page: ap } = await newPage(browser, { viewport: { width: 1366, height: 900 } }, 'admin');
    await step('[flow] admin logs in, sees pending lawyer with documents, approves', async () => {
        await ap.goto('/login');
        await ap.fill('#f_email', ADMIN_EMAIL);
        await ap.fill('#f_password', ADMIN_PASSWORD);
        await ap.getByRole('button', { name: 'Log in' }).click();
        await ap.waitForURL(/\/admin$/);
        await shot(ap, '20-dashboard');
        await ap.goto('/admin/lawyers?status=pending&q=' + encodeURIComponent(lawyerEmail));
        await ap.getByRole('link', { name: 'Review' }).first().click();
        await ap.getByTestId('review-panel').waitFor();
        const body = await ap.content();
        assert(body.includes('9876500011'), 'admin should see private phone');
        assert((await ap.locator('a[href*="/admin/documents/"]').count()) === 2, 'expected 2 documents');
        slug = ap.url().split('/').pop();
        await shot(ap, '21-review');
        // Reject first without reason → validation error
        await ap.check('input[value=reject]');
        await ap.getByTestId('review-submit').click();
        assert((await ap.content()).includes('Please give a reason'), 'reason validation missing');
        await ap.check('input[value=approve]');
        await ap.getByTestId('review-submit').click();
        await ap.waitForLoadState('networkidle');
        assert((await ap.getByTestId('admin-status').innerText()).includes('Approved'), 'not approved');
    });

    await step('[flow] approved lawyer is publicly searchable', async () => {
        await lp.goto('/lawyers?q=' + encodeURIComponent('Etoe Tester ' + stamp));
        const n = await lp.getByTestId('lawyer-card').count();
        assert(n === 1, 'expected 1 result, got ' + n);
    });

    await step('[flow] lawyer edit goes live and appears in admin change log', async () => {
        await lp.goto('/lawyer/profile');
        await lp.fill('#f_bio', 'UPDATED BIO from e2e ' + stamp);
        await lp.getByTestId('save-profile').click();
        await lp.waitForURL(/\/lawyer$/);
        await lp.goto('/lawyers/' + slug);
        assert((await lp.content()).includes('UPDATED BIO from e2e ' + stamp), 'edit not live');
        await ap.goto('/admin/changes');
        assert((await ap.getByTestId('change-logs').innerText()).includes('UPDATED BIO from e2e ' + stamp), 'change not logged');
        await shot(ap, '22-changes');
    });

    await step('[flow] admin enquiry inbox shows the e2e enquiries', async () => {
        await ap.goto('/admin/enquiries?q=E2E+Client');
        const rows = await ap.getByTestId('enquiries-table').locator('tbody tr').count();
        assert(rows >= 2, 'expected enquiries from both devices, got ' + rows);
        await shot(ap, '23-enquiries');
    });

    await step('[flow] admin pages on mobile have no overflow', async () => {
        const { ctx, page } = await newPage(browser, { ...devices['iPhone 13'] }, 'admin-mobile');
        await page.goto('/login');
        await page.fill('#f_email', ADMIN_EMAIL);
        await page.fill('#f_password', ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Log in' }).click();
        await page.waitForURL(/\/admin$/);
        await noOverflow(page);
        await page.getByRole('button', { name: 'Open menu' }).click();
        await page.waitForTimeout(300);
        await shot(page, '24-admin-mobile-menu');
        await ctx.close();
    });

    await step('[flow] no JavaScript errors in flows', async () => {
        assert(lp.errors.length === 0 && ap.errors.length === 0, [...lp.errors, ...ap.errors].join(' | '));
    });

    await browser.close();
    const failed = results.filter((r) => r[0] === 'FAIL');
    console.log(`\n${results.length - failed.length}/${results.length} UI checks passed`);
    process.exit(failed.length ? 1 : 0);
})();
