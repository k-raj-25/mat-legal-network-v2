# MAT LEGAL NETWORK

The website of MAT LEGAL NETWORK, a law firm, with a directory of verified lawyers (India first, ready to go global later).

- **Clients** search lawyers by state, city, practice area, name, experience, language, court, gender, fee and online consultation. They **never see a lawyer's phone number**. They contact the company instead, by helpline call, WhatsApp, or a callback request.
- **Lawyers** register with a 5-step form and upload their Bar enrolment proof and ID. Profiles stay **pending until the admin approves them**. After approval, lawyers can edit their profile. Edits go live immediately and are **logged for the admin to review**.
- **Admin** (the company) approves, rejects with a reason, suspends or reinstates lawyers, views private documents (every view is logged), handles client enquiries and assigns them to lawyers, reviews profile changes, manages the filter lists, edits company contact and legal details, and sees a full audit log. The admin also runs the **blog** and creates **editor (author) accounts**.
- **Editors** sign in and manage **only their own blog posts**: write, save drafts, publish, unpublish and delete, with a featured image and a rich text editor. They cannot see lawyers, enquiries, settings or any other part of the admin panel.

Built with **Laravel 13 + MySQL + Blade + Tailwind CSS 4 + Alpine.js**. The design is bold and vibrant, light theme by default: electric teal (primary), coral (accent) and ink, set in Plus Jakarta Sans. Colour tokens live in `resources/css/app.css`, and every text/background pairing meets WCAG AA contrast. It needs no Node.js, Redis or background workers on the server, so it runs on **cheap shared hosting** (cPanel/Hostinger-style).

---

## Features at a glance

| Area | What's included |
|---|---|
| Privacy | Lawyers' phone, email, address, enrolment number and documents are never sent to public pages. Only whitelisted columns are queried, and the model hides private fields when serialised. Documents sit on a private disk and are streamed only to the owner and the admin. |
| Contact | Company helpline (`tel:`), WhatsApp deep link pre-filled with the lawyer's Profile ID, and a callback form that creates an enquiry with a reference number. |
| Approval workflow | `pending → approved / rejected (with reason) → resubmit → …`, plus `suspended` and reinstate. The lawyer is emailed at each step. |
| Change log | Every edit is stored as an old → new diff. The admin can mark edits reviewed one by one or all at once. |
| Legal | Terms of Use, Privacy Policy (DPDP Act 2023), BCI-compliant Disclaimer (with a first-visit popup), Lawyer Listing Terms, Cookie Policy, IP & Copyright Policy, and Grievance Redressal (IT Rules 2021). Company details in these pages are editable from **Admin › Settings**. |
| Anti-abuse / anti-copying | Rate limits (search 90/min, enquiries 5/hour, login, register, password reset), a honeypot and time-trap on forms, a 100-page cap on deep pagination, robots.txt blocking AI/data crawlers, security headers, and copyright notices. |
| SEO | Server-rendered pages, clean filter URLs, canonical tags, sitemap.xml, and per-profile meta descriptions. Blog posts have their own SEO title/description, Open Graph tags and `BlogPosting` structured data. |
| Blog | Public `/blog` (paginated, newest first), category pages and article pages with a legal-information note. Admin and editors write posts in the panel; HTML is cleaned with an allow-list so a post can never run scripts. |
| Global-ready | Countries table (India active; US, UK, UAE and others seeded but inactive), country-aware phone validation, and currency and bar-body label per country. |

---

## Run it on Windows (one click)

**You need:** Windows 10 (version 1803 or newer) or Windows 11, and an internet connection for the first run. Nothing else: no MySQL, Node.js or XAMPP. If you already have PHP 8.3+ with the right extensions, it is used; otherwise the launcher downloads a private copy of PHP into the `.tools` folder (your system is not changed).

1. Get the code: `git clone https://github.com/k-raj-25/mat-legal-network-v2.git`, or download the ZIP from GitHub and unzip it.
2. Double-click **`start.bat`** in the project folder.
3. The first run takes a few minutes (downloads PHP and the PHP packages, creates the database, adds 60 demo lawyers). Your browser then opens **http://127.0.0.1:8000**.
4. Keep the black window open while you use the site. Press **Ctrl+C** in it (or close it) to stop.

Next time, double-click `start.bat` again; it skips everything that is already done and starts in a few seconds.

| What | Where |
|---|---|
| Public site | http://127.0.0.1:8000 |
| Log in (admin and lawyers) | http://127.0.0.1:8000/login |
| Admin panel | http://127.0.0.1:8000/admin |
| Admin login | `admin@example.com` / `Admin@12345` |
| Demo lawyer logins | `demo.lawyer2@example.com`, `demo.lawyer3@example.com`, … (up to `demo.lawyer61`) / `password` |
| Demo blog editor login | `editor@example.com` / `Editor@12345` (can only manage blog posts) |
| Blog | http://127.0.0.1:8000/blog (3 sample posts: 2 published, 1 draft) |
| E-mails the site "sends" | written to `storage\logs\laravel.log` |
| Database | `database\database.sqlite` (a single file) |

Good to know:

- **Port busy?** If 8000 is taken (for example, the site is already running), the launcher picks 8001, 8002, … and prints the address it used.
- **Start over with fresh demo data:** run `start.bat reset` from a terminal in the project folder. This deletes everything in the local database.
- **Change the admin password:** run `.tools\php\php.exe artisan mat:create-admin admin@example.com` (or `php artisan …` if you use your own PHP). Changing `ADMIN_PASSWORD` in `.env` after the first run has no effect.
- **Use MySQL instead of SQLite:** edit `.env` and set `DB_CONNECTION=mysql` plus the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` lines (create the database first). The launcher uses whatever `.env` says. Name search uses MySQL FULLTEXT, and a simpler `LIKE` search on SQLite.
- **Options:** `set MAT_PORT=9000` before running picks another starting port, and `set MAT_NO_BROWSER=1` stops it opening the browser.
- **Something broke?** Delete the `.tools` and `vendor` folders and run `start.bat` again. To reset your settings too, also delete `.env` and `database\database.sqlite`.
- **Turn on AI spam screening (optional):** see [AI spam screening](#ai-spam-screening-of-enquiries) below. Paste your key into `.env` as `ANTHROPIC_API_KEY=...` and run `start.bat` again. Without a key the site works exactly as before.

---

## AI spam screening of enquiries

Every new enquiry can be checked by Claude (Anthropic's AI). Enquiries it is confident are spam (SEO pitches, link dropping, scams, gibberish) are **held in Admin › Enquiries › Suspected spam** instead of reaching the New inbox. No new-enquiry alert is sent for them and nobody is assigned. The admin sees the AI's reason and confidence and clicks **Confirm spam** (it moves to Spam) or **Not spam** (it moves back to New), one at a time or for a whole page at once. Nothing is ever deleted.

**Turn it on**

1. Create an API key at [console.anthropic.com](https://console.anthropic.com) › API keys (needs billing set up).
2. Put it in `.env` (never in Git, never in `.env.example`):

   ```ini
   ANTHROPIC_API_KEY=sk-ant-...
   ```

   Locally, run `start.bat` again (or `php artisan config:clear`). On the server, run `php artisan optimize` after editing `.env`.

| Setting | Default | What it does |
|---|---|---|
| `ANTHROPIC_API_KEY` | empty | Off when empty. |
| `SPAM_AI_ENABLED` | `true` | Set `false` to pause screening without removing the key. |
| `SPAM_AI_MODEL` | `claude-opus-5-5` | Any Claude model. `claude-haiku-5-5` is much cheaper and fast enough for this. |
| `SPAM_AI_THRESHOLD` | `80` | How sure (0–100) the AI must be before it holds an enquiry back. Raise it if real enquiries get held. |
| `SPAM_AI_TIMEOUT` | `20` | Seconds to wait for the AI before giving up. |

**Safe by design**

- **Never loses an enquiry.** It is saved first, then checked. If there is no key, or the API fails, times out, refuses or answers nonsense, the enquiry is handled as normal and a warning is written to `storage/logs/laravel.log`.
- **Doesn't slow the form.** The check runs on the queue (the cron job on the server). Locally (`start.bat`) it runs just after the page has been sent.
- **Minimal data.** Only the message text, the practice area and the form type are sent. Name, phone, e-mail, city and IP address are never sent.
- **Prompt-injection resistant.** The message is passed as escaped data with instructions to never obey it, and the answer must match a strict format. The worst a trick message can do is be treated as a normal enquiry.

---

## Blog and editor accounts

The public blog is at **/blog**, with a category menu, pages of 9 posts (newest first) and one page per article. It is linked from the header, the mobile menu, the footer and `sitemap.xml`. Every article ends with a short note that it is general information, that MAT LEGAL NETWORK is a law firm and gives legal advice when engaged, and a link to the Disclaimer.

**Admin**

- **Admin › Blog posts:** every post by every author. Filter by author or status, search by title, edit, unpublish or delete anything.
- **Admin › Blog editors:** add an editor (name, email, temporary password), change their details, **reset their password** (signs them out everywhere) and **deactivate** or reactivate them. A deactivated editor cannot sign in; their published posts stay on the blog.
- **Admin › Blog categories:** add categories or hide them. Seven starter categories are created by `php artisan db:seed`.
- Every create, edit, publish, unpublish and delete is in the **Audit log**.

**Editors** sign in at `/login` and land on **My blog posts**. They see only their own posts and **My account** (change password). Writing a post:

1. **Title**, optional **web address** (made from the title if empty) and **summary**.
2. **Article** in the editor: headings, bold, italic, lists, quotes and links. Pasted text arrives as plain text. Without JavaScript the editor falls back to a plain HTML box.
3. Optional **category**, **featured image** (JPG/PNG/WebP up to 4 MB, needs a description for screen readers) and **SEO title/description**.
4. **Save draft** (only the author and the admin can see it, with **Preview**) or **Publish**. A live post can be **Updated** or **Unpublished** back to a draft.

**How access is enforced:** the editor role can reach only the `admin/posts*` and `admin/account*` routes (`role:admin,editor` middleware). Everything else under `/admin` stays `role:admin` and returns 403 for editors. On top of that, `app/Policies/PostPolicy.php` lets an editor view, edit, publish or delete **only posts they wrote**, and `app/Policies/UserPolicy.php` keeps editor management with the admin. `tests/Feature/EditorAccessTest.php` walks every admin route and proves editors are refused.

**Safe HTML:** post bodies are cleaned on save by `app/Support/HtmlSanitizer.php` (HTML Purifier with an allow-list: paragraphs, headings, bold/italic, lists, quotes, links and code). Scripts, styles, event handlers, iframes, images in the body and `javascript:` links are removed.

**Demo data:** `php artisan db:seed --class=BlogDemoSeeder` adds the demo editor and 3 sample posts (`start.bat` does this for you once, also on an existing local database). For load testing: `DEMO_POSTS=20000 php artisan db:seed --class=BlogDemoSeeder`.

---

## Local development

Requirements: PHP 8.3+, Composer, MySQL 8 / MariaDB 10.6+, Node 20+ (only to rebuild CSS/JS).

```bash
composer install
```

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

Edit `.env`: set the database credentials, `ADMIN_EMAIL`, `ADMIN_PASSWORD` and `ADMIN_NOTIFY_EMAIL`. Then:

```bash
php artisan migrate --seed
```

```bash
php artisan storage:link
```

```bash
php artisan db:seed --class=DemoSeeder
```

```bash
php artisan serve
```

Open http://localhost:8000. Log in at `/login` with the admin credentials from `.env`. Demo lawyers use the password `password`.

Front-end assets are **pre-built and committed** in `public/build`. Rebuild only after changing CSS/JS/Blade classes:

```bash
npm install && npm run build
```

---

## Tests

### 1. Feature & unit tests (140 tests, 1402 assertions)

They run against a real MySQL database, `mat_legal_test` (see `phpunit.xml`):

```bash
php artisan test
```

They cover search filters and sorting, privacy leaks, registration validation and uploads, the approval workflow, change logs, the enquiry anti-spam measures and rate limits, auth and password reset, every admin page, the legal pages and the security headers.

### 2. Browser UI tests (Playwright, desktop + iPhone)

33 checks: disclaimer (including "Read full disclaimer" and Back), hero search with dependent dropdowns, filters and chips, mobile filter drawer, profile privacy, enquiry form, the full register → approve → edit → change-log flow, no horizontal overflow, and no JS errors.

```bash
php artisan cache:clear
```

```bash
BASE_URL=http://127.0.0.1:8000 ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD='your-password' node tests/Browser/e2e.cjs
```

**AI spam screening (15 checks, desktop + iPhone).** Uses a fake Anthropic API (`tests/Browser/fake-anthropic.cjs`), never the real one. Add these lines to `.env`, run `php artisan config:clear`, restart `php artisan serve`, then run the script. Remove the lines afterwards.

```ini
ANTHROPIC_API_KEY=test-key
SPAM_AI_BASE_URL=http://127.0.0.1:8787
QUEUE_CONNECTION=sync
```

```bash
node tests/Browser/spam-e2e.cjs
```

**Blog and editor accounts (22 checks, desktop + iPhone).** Needs the demo data from `DemoSeeder` (which adds the sample posts) and `php artisan storage:link`.

```bash
node tests/Browser/blog-e2e.cjs
```

### 3. Scalability

```bash
DEMO_LAWYERS=50000 php artisan db:seed --class=DemoSeeder
```

```bash
node tests/Load/load.cjs http://127.0.0.1:8000 20 10 /lawyers "/lawyers?q=sharma"
```

Results measured on 50,000 lawyers (4 vCPU, PHP 8.3 with OPcache, MariaDB 10.11):

| Search | DB time |
|---|---|
| No filters / state / state+city | 7–18 ms |
| Practice area / language+court | 18–30 ms |
| Name (FULLTEXT) / short-name LIKE | 8–35 ms |
| All filters combined | 10 ms |
| Page 50, sorted "newest" | 16 ms |

Under load (20 concurrent users, 0 errors): search pages 100–120 req/s, profile pages about 150 req/s, home page about 170 req/s.

---

## Deploying to shared hosting (cPanel / Hostinger / similar)

1. **Create a MySQL database and user** in the hosting panel.
2. **Upload the code** (Git deploy, or a zip of the repo *including* `vendor/` if Composer isn't available on the host) to a folder **outside** `public_html`, for example `~/matlegal`.
3. **Point the domain to `~/matlegal/public`.** Either set the domain's document root to that folder in the panel, or, if you can't change it, copy the contents of `public/` into `public_html` and edit the two paths in `public_html/index.php` to `../matlegal/vendor/autoload.php` and `../matlegal/bootstrap/app.php`.
4. **Create `.env`** from `.env.example` and set:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`
   - `DB_*` credentials
   - `MAIL_MAILER=smtp` plus the SMTP details from your host or email provider
   - `ADMIN_EMAIL`, `ADMIN_PASSWORD` (strong!), `ADMIN_NOTIFY_EMAIL`
5. **Run these commands once** (from the panel's Terminal, or over SSH):

   ```bash
   php artisan key:generate --force
   ```

   ```bash
   php artisan migrate --seed --force
   ```

   ```bash
   php artisan storage:link
   ```

   ```bash
   php artisan optimize
   ```

   After the first login, **remove `ADMIN_PASSWORD` from `.env`**. To reset the admin password later, run `php artisan mat:create-admin you@domain.com`.
6. **Add one cron job** (Panel › Cron Jobs, every minute). It sends queued emails and runs maintenance:

   ```bash
   * * * * * cd ~/matlegal && php artisan schedule:run >> /dev/null 2>&1
   ```

7. **Enable SSL** (free Let's Encrypt in most panels). The app forces HTTPS in production.
8. **Log in** at `https://your-domain/login`, open **Admin › Settings**, and enter the real helpline number, WhatsApp number, email, registered address, legal entity name, jurisdiction city and Grievance Officer. These are used across the site and in the legal pages.

**If `storage:link` isn't allowed** (some hosts block symlinks), ask the host's support to create the link from `public/storage` to `storage/app/public`. Profile photos are the only public uploads; verification documents stay private regardless.

Recommended PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`. These are standard on most hosts. Make sure **OPcache** is enabled; it makes the app 3–4× faster.

---

## Protecting the business

The code adds terms, copyright notices, robots.txt rules and rate limits. Strong protection also needs a few steps outside the code:

1. **Have a practising lawyer review the legal pages** before launch, especially Terms, Privacy and Lawyer Listing Terms. They are thorough templates for Indian law, but they are not a substitute for advice on your exact business structure.
2. **Register the trademark "MAT LEGAL NETWORK"** and the logo with the Indian Trade Marks Registry (classes 45 and 35, optionally 42). Until it is registered, use ™, not ®.
3. **Keep this GitHub repository private.** The `LICENSE` file is proprietary (all rights reserved).
4. Consider putting the site behind **Cloudflare** (free plan) for extra bot and scraping protection, and add Cloudflare Turnstile to forms if spam grows.
5. Note that a *business idea* itself cannot be copyrighted. What is protected is your brand, your code and design, your content and your compiled directory data.

---

## Project map

```
app/Services/LawyerSearch.php          public search (filters, sorting, FULLTEXT)
app/Services/LawyerProfileService.php  registration, edits, change-log diffs, documents
app/Services/LawyerReviewService.php   approve / reject / suspend + emails + audit
app/Http/Controllers/Admin/*           admin panel
app/Http/Controllers/Lawyer/*          lawyer portal
app/Support/SpamGuard.php              honeypot + time-trap
resources/views/legal/*                legal documents
database/data/*.php                    Indian states/cities, practice areas, languages, courts, bar councils
tests/Feature, tests/Unit              PHPUnit tests
tests/Browser/e2e.cjs                  Playwright UI tests
tests/Browser/spam-e2e.cjs             Playwright checks for AI spam screening (fake AI API)
app/Services/Spam/                     AI spam screening (classifier, fail-open flow)
app/Services/PostService.php           blog posts: save, publish/unpublish, images, audit
app/Policies/PostPolicy.php            who may manage which blog post (admin: all, editor: own)
app/Support/HtmlSanitizer.php          allow-list HTML cleaning for blog posts
tests/Browser/blog-e2e.cjs             Playwright checks for the blog and editor accounts
tests/Load/load.cjs                    load test script
```
