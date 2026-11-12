/**
 * Browser checks for AI spam screening (desktop + iPhone). Uses a fake Anthropic API, never the real one.
 *   1. In .env: ANTHROPIC_API_KEY=test-key  SPAM_AI_BASE_URL=http://127.0.0.1:8787  QUEUE_CONNECTION=sync
 *   2. php artisan config:clear && php artisan serve
 *   3. NODE_PATH=$(npm root -g) node tests/Browser/spam-e2e.cjs     (starts the fake API itself)
 */
const { chromium, devices } = require('playwright');
const fs = require('fs');
const path = require('path');
const fake = require('./fake-anthropic.cjs');
const { execSync } = require('child_process');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'Admin@12345';
const SHOTS = process.env.SHOTS_DIR || path.join(__dirname, '../../storage/e2e');
fs.mkdirSync(SHOTS, { recursive: true });

const results = [];
async function step(name, fn) {
    try { await fn(); results.push(['PASS', name]); console.log('PASS', name); }
    catch (e) { results.push(['FAIL', name]); console.log('FAIL', name, '\n   ', e.message.split('\n').slice(0, 3).join('\n    ')); }
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
async function shot(page, name) { await page.screenshot({ path: path.join(SHOTS, `${page.label}-${name}.png`), fullPage: true }); }
async function acceptDisclaimer(page) {
    const btn = page.getByTestId('disclaimer-accept');
    if (await btn.isVisible().catch(() => false)) await btn.click();
}
async function login(page) {
    await page.goto('/login');
    await page.fill('#f_email', ADMIN_EMAIL);
    await page.fill('#f_password', ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForURL(/\/admin$/);
}

(async () => {
    const api = await fake.start();
    const browser = await chromium.launch();
    const stamp = Date.now().toString().slice(-6);

    const devicesToTest = [['desktop', { viewport: { width: 1366, height: 900 } }], ['iphone', { ...devices['iPhone 13'] }]];

    for (const [label, opts] of devicesToTest) {
        // The form allows 5 enquiries per hour per IP; reset the limiter so both devices can submit.
        execSync('php artisan cache:clear', { cwd: path.join(__dirname, '../..'), stdio: 'ignore' });
        const { ctx, page } = await newPage(browser, opts, label);
        const send = async (name, phone, message) => {
            await page.goto('/contact');
            await acceptDisclaimer(page);
            const form = page.getByTestId('enquiry-form');
            await page.waitForTimeout(3200); // the form's anti-bot timer
            await form.getByLabel('Your name').fill(name);
            await form.getByLabel('Mobile number').fill(phone);
            await form.locator('textarea[name=message]').fill(message);
            await form.locator('input[name=consent]').check();
            await page.getByTestId('enquiry-submit').click();
            await page.getByTestId('enquiry-success').waitFor();
        };

        await step(`[${label}] visitor gets the normal success screen for spam, genuine and API-failure enquiries`, async () => {
            await send(`Spam Bot ${label} ${stamp}`, '9811100001', `MOCKSPAM We sell SEO backlinks, rank #1 on Google! visit cheap-seo.example ${stamp}`);
            await send(`Spam Two ${label} ${stamp}`, '9811100002', `MOCKSPAM Guest post offer for your law blog ${stamp}`);
            await send(`Genuine Client ${label} ${stamp}`, '9811100003', `My landlord is refusing to return my deposit, please call ${stamp}`);
            await send(`Fail Open ${label} ${stamp}`, '9811100004', `MOCKFAIL Need help with a cheque bounce notice ${stamp}`);
            await shot(page, 'spam-01-visitor-success');
            await noOverflow(page);
        });
        await ctx.close();

        const { ctx: actx, page: ap } = await newPage(browser, opts, label + '-admin');
        await login(ap);

        await step(`[${label}] dashboard points the admin to the suspected spam`, async () => {
            const link = ap.getByTestId('dashboard-suspected-spam');
            assert(await link.isVisible(), 'no suspected spam link on dashboard');
            await noOverflow(ap);
            await link.click();
            await ap.waitForURL(/status=suspected_spam/);
        });

        await step(`[${label}] suspected spam queue shows AI reason and confidence, only for spam`, async () => {
            const queue = ap.getByTestId('spam-queue');
            const txt = await queue.innerText();
            assert(txt.includes(`Spam Bot ${label} ${stamp}`) && txt.includes(`Spam Two ${label} ${stamp}`), 'spam enquiries missing from queue');
            assert(!txt.includes(`Genuine Client ${label} ${stamp}`), 'genuine enquiry wrongly in spam queue');
            assert(!txt.includes(`Fail Open ${label} ${stamp}`), 'failed check wrongly in spam queue');
            assert(txt.includes('97% spam') && txt.includes('Unsolicited SEO sales pitch'), 'reason/confidence not shown');
            await noOverflow(ap);
            await shot(ap, 'spam-02-queue');
        });

        await step(`[${label}] genuine and API-failure enquiries are in New`, async () => {
            await ap.goto('/admin/enquiries?status=new&q=' + encodeURIComponent(stamp));
            const t = await ap.getByTestId('enquiries-table').innerText();
            assert(t.includes(`Genuine Client ${label} ${stamp}`), 'genuine missing from New');
            assert(t.includes(`Fail Open ${label} ${stamp}`), 'fail-open enquiry missing from New');
            assert(!t.includes(`Spam Bot ${label} ${stamp}`), 'spam shown in New');
        });

        await step(`[${label}] enquiry detail shows the AI panel; "Not spam" moves it to New`, async () => {
            await ap.goto('/admin/enquiries?status=suspected_spam');
            const item = ap.getByTestId('spam-item').filter({ hasText: `Spam Two ${label} ${stamp}` });
            await item.getByRole('link', { name: 'Open' }).click();
            const panel = ap.getByTestId('spam-panel');
            await panel.waitFor();
            assert((await panel.innerText()).includes('Held back for your review'), 'panel text');
            await noOverflow(ap);
            await shot(ap, 'spam-03-detail');
            await panel.getByTestId('not-spam').click();
            await ap.getByRole('status').filter({ hasText: 'marked not spam' }).waitFor();
            assert((await ap.getByTestId('spam-review').innerText()).includes('Marked not spam'), 'review not recorded');
            await shot(ap, 'spam-04-not-spam');
        });

        await step(`[${label}] select all + Confirm spam files the rest as Spam`, async () => {
            await ap.goto('/admin/enquiries?status=suspected_spam');
            const bulkBtn = ap.getByTestId('bulk-confirm-spam');
            assert(await bulkBtn.isDisabled(), 'bulk button should be disabled with nothing selected');
            await ap.getByTestId('spam-select-all').check();
            assert(!(await bulkBtn.isDisabled()), 'bulk button still disabled');
            await shot(ap, 'spam-05-bulk-selected');
            await bulkBtn.click();
            await ap.getByRole('status').filter({ hasText: 'confirmed as spam' }).waitFor();
            await ap.getByTestId('spam-queue-empty').waitFor();
            await ap.goto('/admin/enquiries?status=spam&q=' + encodeURIComponent(stamp));
            const t = await ap.getByTestId('enquiries-table').innerText();
            assert(t.includes(`Spam Bot ${label} ${stamp}`), 'not moved to Spam');
            await ap.goto('/admin/enquiries?status=new&q=' + encodeURIComponent(`Spam Two ${label}`));
            assert((await ap.getByTestId('enquiries-table').innerText()).includes(`Spam Two ${label} ${stamp}`), 'not-spam one is not in New');
            await noOverflow(ap);
        });

        await step(`[${label}] no JavaScript or HTTP errors`, async () => {
            assert(ap.errors.length === 0, ap.errors.join(' | '));
        });
        await actx.close();
    }

    await step('[api] only non-personal data was sent to the AI, with no secrets leaked back', async () => {
        assert(api.requests.length >= 8, 'expected AI calls, got ' + api.requests.length);
        for (const r of api.requests) {
            for (const pii of ['Spam Bot', 'Genuine Client', '98111000', 'admin@example.com']) {
                assert(!r.raw.includes(pii), `request leaked "${pii}"`);
            }
            assert(/test-key/.test(r.headers['authorization'] || r.headers['x-api-key'] || ''), 'API key header missing');
        }
    });

    await browser.close();
    api.server.close();
    const failed = results.filter((r) => r[0] === 'FAIL');
    console.log(`\n${results.length - failed.length}/${results.length} spam screening UI checks passed`);
    process.exit(failed.length ? 1 : 0);
})();
