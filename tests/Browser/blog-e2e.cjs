/**
 * Blog + editor accounts UI checks (desktop + iPhone) using Playwright.
 *   php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder && php artisan storage:link && php artisan serve
 *   BASE_URL=http://127.0.0.1:8000 ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD=... node tests/Browser/blog-e2e.cjs
 * Needs the sample posts from BlogDemoSeeder (DemoSeeder runs it). Screenshots go to SHOTS_DIR (default ./storage/e2e).
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
    page.expected = new Set(); // URLs where a 403/404 is the point of the check
    page.on('pageerror', (e) => page.errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error' && !/status of 40[34]/.test(m.text())) page.errors.push(m.text() + ' @ ' + page.url()); });
    page.on('response', (r) => { if (r.status() >= 400 && r.status() !== 422 && !page.expected.has(new URL(r.url()).pathname)) page.errors.push(`HTTP ${r.status()} ${r.url()}`); });
    page.on('dialog', (d) => d.accept());
    page.label = label;
    return { ctx, page };
}
async function noOverflow(page) {
    const o = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    assert(o <= 1, `horizontal overflow of ${o}px on ${page.url()}`);
}
async function acceptDisclaimer(page) {
    const btn = page.getByTestId('disclaimer-accept');
    if (await btn.isVisible().catch(() => false)) await btn.click();
}
async function shot(page, name, full = true) { await page.screenshot({ path: path.join(SHOTS, `${page.label}-blog-${name}.png`), fullPage: full }); }
async function login(page, email, password) {
    await page.goto('/login');
    await page.locator('#f_email').fill(email);
    await page.locator('#f_password').fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
}
async function logout(page) {
    await page.context().clearCookies();
}
async function openAdminMenu(page) {
    const btn = page.getByRole('button', { name: 'Open menu' });
    if (await btn.isVisible()) { await btn.click(); await page.waitForTimeout(300); }
}
function noErrors(page) {
    const errs = page.errors.splice(0);
    assert(errs.length === 0, 'errors: ' + errs.join(' | '));
}

(async () => {
    const browser = await chromium.launch();
    const png = path.join(SHOTS, 'fixtures', 'blog-photo.png');
    fs.mkdirSync(path.dirname(png), { recursive: true });
    // 2x1 teal PNG
    fs.writeFileSync(png, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAIAAAABCAIAAAB7QOjdAAAAEElEQVR42mPgamCAIAYGBgAKbgDBBS2kgQAAAABJRU5ErkJggg==', 'base64'));

    for (const [label, opts] of [['desktop', { viewport: { width: 1366, height: 900 }, deviceScaleFactor: 2 }], ['iphone', { ...devices['iPhone 13'] }]]) {
        const { ctx, page } = await newPage(browser, opts, label);
        const stamp = Date.now().toString().slice(-6);
        const editorEmail = `writer.${label}.${stamp}@example.com`;
        const editorPass = 'Writer1234';
        const postTitle = `Know your rights at a police station ${stamp}`;
        let postUrl = null;

        await step(`[${label}] header links to the blog`, async () => {
            await page.goto('/');
            await acceptDisclaimer(page);
            if (label === 'iphone') {
                await page.getByRole('button', { name: 'Toggle menu' }).click();
                await page.locator('#mobile-menu').getByRole('link', { name: 'Blog' }).click();
            } else {
                await page.getByRole('navigation', { name: 'Main' }).getByRole('link', { name: 'Blog' }).click();
            }
            await page.waitForURL(/\/blog$/);
            noErrors(page);
        });

        await step(`[${label}] blog index lists published sample posts only`, async () => {
            await page.getByTestId('blog-heading').waitFor();
            const cards = await page.getByTestId('post-card').count();
            assert(cards >= 2, `expected 2+ cards, got ${cards}`);
            const text = await page.locator('main').innerText();
            assert(text.includes('Cheque bounce in India'), 'sample post missing');
            assert(!text.includes('How to file a consumer complaint online'), 'draft is visible');
            await noOverflow(page);
            await shot(page, '01-index');
            noErrors(page);
        });

        await step(`[${label}] category menu filters posts`, async () => {
            await page.getByRole('navigation', { name: 'Blog categories' }).getByRole('link', { name: 'Family & divorce' }).click();
            await page.waitForURL(/\/blog\/category\/family-divorce$/);
            const text = await page.locator('main').innerText();
            assert(text.includes('Mutual consent divorce'), 'family post missing');
            assert(!text.includes('Cheque bounce in India'), 'other category shown');
            await noOverflow(page);
            noErrors(page);
        });

        await step(`[${label}] article page: content, legal note, SEO tags`, async () => {
            await page.getByRole('link', { name: /Mutual consent divorce/ }).first().click();
            await page.waitForURL(/\/blog\/mutual-consent-divorce-process$/);
            await page.getByTestId('post-title').waitFor();
            const note = await page.getByTestId('post-disclaimer').innerText();
            assert(/is a law firm and gives legal advice when you engage it/.test(note), 'legal note wording');
            assert(await page.locator('script[type="application/ld+json"]').count() === 1, 'JSON-LD missing');
            assert(await page.locator('meta[property="og:type"][content="article"]').count() === 1, 'og:type');
            const h2 = await page.getByTestId('post-body').locator('h2').count();
            assert(h2 >= 2, 'article headings missing');
            await noOverflow(page);
            await shot(page, '02-article');
            noErrors(page);
        });

        await step(`[${label}] admin creates an editor account`, async () => {
            await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
            await page.waitForURL(/\/admin$/);
            await openAdminMenu(page);
            await page.getByTestId('admin-sidebar').getByRole('link', { name: 'Blog editors' }).click();
            await page.waitForURL(/\/admin\/editors$/);
            await page.getByTestId('new-editor').click();
            await page.getByLabel('Name (shown on their posts)').fill(`Writer ${label} ${stamp}`);
            await page.getByLabel('Email (used to sign in)').fill(editorEmail);
            await page.getByLabel('Temporary password').fill(editorPass);
            await page.getByLabel('Confirm password').fill(editorPass);
            await page.getByRole('button', { name: 'Create editor' }).click();
            await page.getByText('Editor account created').waitFor();
            await page.goto('/admin/editors');
            assert((await page.getByTestId('editors-table').innerText()).includes(editorEmail), 'editor not listed');
            await noOverflow(page);
            await shot(page, '03-admin-editors');
            noErrors(page);
        });

        await step(`[${label}] editor sees only blog menu and is refused elsewhere`, async () => {
            await logout(page);
            await login(page, editorEmail, editorPass);
            await page.waitForURL(/\/admin\/posts$/);
            await openAdminMenu(page);
            const links = await page.getByTestId('admin-sidebar').getByRole('navigation').getByRole('link').allInnerTexts();
            assert(JSON.stringify(links.map((l) => l.trim())) === JSON.stringify(['My blog posts', 'My account']), 'menu: ' + links.join(','));
            await shot(page, '04-editor-menu', false);
            for (const url of ['/admin', '/admin/lawyers', '/admin/enquiries', '/admin/settings', '/admin/editors', '/admin/audit-log']) {
                page.expected.add(url);
                const res = await page.goto(url);
                assert(res.status() === 403, `${url} gave ${res.status()}`);
            }
            noErrors(page);
        });

        await step(`[${label}] editor writes and publishes a post with the rich text editor`, async () => {
            await page.goto('/admin/posts');
            await page.getByTestId('new-post').click();
            await page.locator('#f_title').fill(postTitle);
            await page.getByLabel('Summary').fill('What the law says about your rights if the police call you in.');
            const ed = page.getByTestId('body-editor');
            await ed.click();
            await page.keyboard.type('If the police ask you to come to the station, you have rights.');
            await page.keyboard.press('Enter');
            await page.getByRole('button', { name: 'Heading', exact: true }).click();
            await page.keyboard.type('Your key rights');
            await page.keyboard.press('Enter');
            await page.getByRole('button', { name: 'Paragraph' }).click();
            await page.getByRole('button', { name: 'Bulleted list' }).click();
            await page.keyboard.type('The right to know the grounds of arrest');
            await page.keyboard.press('Enter');
            await page.keyboard.type('The right to consult a lawyer');
            await page.keyboard.press('Enter');
            await page.keyboard.press('Enter');
            await page.keyboard.type('Stay calm and ask for ');
            await page.keyboard.down('Control'); await page.keyboard.press('b'); await page.keyboard.up('Control');
            await page.keyboard.type('written reasons');
            await page.keyboard.down('Control'); await page.keyboard.press('b'); await page.keyboard.up('Control');
            await page.keyboard.type('.');
            const words = await page.getByTestId('rich-editor').getByText(/words ·/).innerText();
            assert(/^\d+ words/.test(words.trim()) && parseInt(words) > 20, 'word count: ' + words);
            await page.locator('#f_blog_category_id').selectOption({ label: 'Criminal law' });
            await page.locator('#f_featured_image').setInputFiles(png);
            await page.getByLabel('Image description (alt text)').fill('A teal banner');
            await shot(page, '05-editor-form');
            await page.getByTestId('publish-post').click();
            await page.getByText('Post published').waitFor();
            await page.getByTestId('publish-box').getByText('Published').first().waitFor();
            postUrl = await page.getByRole('link', { name: /View on blog/ }).getAttribute('href');
            assert(postUrl && postUrl.includes('/blog/know-your-rights-at-a-police-station'), 'post url ' + postUrl);
            await noOverflow(page);
            noErrors(page);
        });

        await step(`[${label}] published post shows on the public blog with safe formatting`, async () => {
            await page.goto(postUrl);
            await acceptDisclaimer(page);
            const body = page.getByTestId('post-body');
            assert((await body.locator('h2').innerText()).trim() === 'Your key rights', 'h2');
            assert(await body.locator('ul > li').count() === 2, 'list items');
            assert((await body.locator('strong, b').first().innerText()).includes('written reasons'), 'bold');
            assert(await page.locator('figure img[alt="A teal banner"]').count() === 1, 'featured image');
            await page.goto('/blog');
            assert((await page.getByTestId('post-card').first().innerText()).includes(postTitle), 'new post should lead the index');
            await noOverflow(page);
            await shot(page, '06-new-post-on-index');
            noErrors(page);
        });

        await step(`[${label}] editor unpublishes, previews and deletes the post`, async () => {
            await page.goto('/admin/posts');
            await page.getByRole('link', { name: postTitle, exact: true }).click();
            await page.getByTestId('unpublish-post').click();
            await page.getByText('Post unpublished').waitFor();
            const slugPath = new URL(postUrl).pathname;
            page.expected.add(slugPath);
            const res = await page.goto(postUrl);
            assert(res.status() === 404, 'unpublished post is public: ' + res.status());
            await page.goBack();
            await page.goto(new URL(page.url()).pathname.replace(/\/edit$/, '/preview'));
            await page.getByTestId('preview-banner').waitFor();
            await page.goBack();
            await page.getByTestId('delete-post').click();
            await page.waitForURL(/\/admin\/posts$/);
            await page.getByText('was deleted').waitFor();
            noErrors(page);
        });

        await step(`[${label}] editor changes own password`, async () => {
            await openAdminMenu(page);
            await page.getByTestId('admin-sidebar').getByRole('link', { name: 'My account' }).click();
            const f = page.getByTestId('account-password-form');
            await f.locator('#f_current_password').fill(editorPass);
            await f.locator('#f_password').fill(editorPass + 'x');
            await f.locator('#f_password_confirmation').fill(editorPass + 'x');
            await f.getByRole('button', { name: 'Update password' }).click();
            await page.getByText('Your password has been changed.').waitFor();
            await noOverflow(page);
            noErrors(page);
        });

        await step(`[${label}] admin sees all posts and deactivates the editor`, async () => {
            await logout(page);
            await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
            await page.goto('/admin/posts');
            const table = await page.getByTestId('posts-table').innerText();
            assert(table.includes('Demo Editor') && table.includes('How to file a consumer complaint'), 'admin should see drafts by every author');
            await noOverflow(page);
            await shot(page, '07-admin-posts');
            await page.goto('/admin/editors');
            await page.getByRole('link', { name: `Writer ${label} ${stamp}`, exact: true }).click();
            await page.getByTestId('toggle-editor').click();
            await page.getByText('Account deactivated').waitFor();
            await shot(page, '08-admin-editor-detail');
            await logout(page);
            await login(page, editorEmail, editorPass + 'x');
            await page.locator('.form-error', { hasText: 'This account has been deactivated' }).waitFor();
            noErrors(page);
        });

        await ctx.close();
    }

    await browser.close();
    const failed = results.filter((r) => r[0] === 'FAIL');
    console.log(`\n${results.length - failed.length}/${results.length} passed`);
    if (failed.length) { failed.forEach((f) => console.log(' -', f[1], ':', f[2])); process.exit(1); }
})();
