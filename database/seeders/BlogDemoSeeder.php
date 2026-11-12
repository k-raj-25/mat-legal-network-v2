<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\Role;
use App\Models\BlogCategory;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo blog data. NEVER run in production.
 *   php artisan db:seed --class=BlogDemoSeeder                  demo editor + 3 sample posts
 *   DEMO_POSTS=20000 php artisan db:seed --class=BlogDemoSeeder  ...plus 20k filler posts (load testing)
 *
 * Demo editor login: editor@example.com / Editor@12345
 */
class BlogDemoSeeder extends Seeder
{
    public const EDITOR_EMAIL = 'editor@example.com';

    public const EDITOR_PASSWORD = 'Editor@12345';

    public function run(): void
    {
        $this->call(BlogCategorySeeder::class);
        $cat = BlogCategory::pluck('id', 'slug');

        $editor = User::firstOrNew(['email' => self::EDITOR_EMAIL]);
        if (! $editor->exists) {
            $editor->forceFill([
                'name' => 'Demo Editor',
                'password' => self::EDITOR_PASSWORD,
                'role' => Role::Editor,
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();
        }

        foreach ($this->samples() as $i => $s) {
            if (Post::where('slug', $s['slug'])->exists()) {
                continue;
            }
            $post = new Post([
                'title' => $s['title'],
                'slug' => $s['slug'],
                'excerpt' => $s['excerpt'],
                'body' => $s['body'],
                'blog_category_id' => $cat[$s['category']] ?? null,
            ]);
            $post->user_id = $editor->id;
            $post->status = $s['status'];
            $post->published_at = $s['status'] === PostStatus::Published ? now()->subDays(3 * ($i + 1)) : null;
            $post->save();
        }

        $this->filler((int) env('DEMO_POSTS', 0), $editor->id, $cat->values()->all());
    }

    /** Bulk insert N published filler posts (for scale tests). */
    private function filler(int $count, int $authorId, array $categoryIds): void
    {
        if ($count <= 0) {
            return;
        }
        $body = str_repeat('<p>This is sample text used to test how the blog performs with a very large number of articles. It explains the law in general terms only.</p>', 8);
        $now = now();
        $start = (int) DB::table('posts')->max('id');
        foreach (array_chunk(range(1, $count), 1000) as $chunk) {
            $rows = [];
            foreach ($chunk as $n) {
                $id = $start + $n;
                $rows[] = [
                    'user_id' => $authorId, 'blog_category_id' => $categoryIds ? $categoryIds[$n % count($categoryIds)] : null,
                    'title' => 'Sample legal guide number '.$id, 'slug' => 'sample-legal-guide-'.$id,
                    'excerpt' => 'A sample article used for load testing the blog.', 'body' => $body,
                    'status' => $n % 10 === 0 ? 'draft' : 'published',
                    'published_at' => $n % 10 === 0 ? null : $now->copy()->subMinutes($count - $n + 60 * 24 * 30),
                    'reading_minutes' => 1, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            DB::table('posts')->insert($rows);
        }
    }

    private function samples(): array
    {
        return [
            [
                'title' => 'Cheque bounce in India: what to do, step by step',
                'slug' => 'cheque-bounce-what-to-do',
                'category' => 'banking-cheques',
                'status' => PostStatus::Published,
                'excerpt' => 'A bounced cheque can be a criminal offence under Section 138 of the Negotiable Instruments Act. Here is the timeline to follow so you do not lose your right to file a case.',
                'body' => <<<'HTML'
<p>When a cheque given to you is returned unpaid because the account has too little money, the law gives you a strong remedy. Under <strong>Section 138 of the Negotiable Instruments Act, 1881</strong>, dishonour of a cheque for insufficient funds can be a criminal offence. But the remedy only works if you follow a strict timeline.</p>
<h2>1. Present the cheque in time</h2>
<p>A cheque is generally valid for <strong>three months</strong> from the date written on it. Present it to your bank within that period.</p>
<h2>2. Keep the return memo</h2>
<p>If the cheque bounces, your bank gives you a <em>cheque return memo</em> stating the reason, such as "funds insufficient". Keep the original memo and the cheque safely.</p>
<h2>3. Send a legal notice within 30 days</h2>
<p>You must send a written demand notice to the person who issued the cheque within <strong>30 days</strong> of receiving the bank's information that it bounced. The notice asks them to pay the cheque amount.</p>
<h2>4. Wait 15 days for payment</h2>
<p>The issuer has <strong>15 days</strong> from receiving your notice to pay. If they pay, the matter usually ends there.</p>
<h2>5. File the complaint within one month</h2>
<p>If they do not pay, you can file a complaint before the magistrate within <strong>one month</strong> after the 15 days end. Courts can accept a late complaint only if there is sufficient cause for the delay.</p>
<blockquote><p>Tip: missing any of these deadlines can weaken or end your case, so note the dates as soon as the cheque bounces.</p></blockquote>
<h3>Documents you will usually need</h3>
<ul><li>The original cheque and the bank's return memo</li><li>A copy of the legal notice and proof it was sent and delivered</li><li>Anything that shows why the money was owed, such as an invoice or agreement</li></ul>
<p>Every case turns on its own facts. If a cheque given to you has bounced, our team can review your documents and advise you on the next step.</p>
HTML,
            ],
            [
                'title' => 'Mutual consent divorce: the process and how long it takes',
                'slug' => 'mutual-consent-divorce-process',
                'category' => 'family-divorce',
                'status' => PostStatus::Published,
                'excerpt' => 'When both spouses agree to separate, a mutual consent divorce is usually the quickest and least stressful route. This guide walks through the steps under the Hindu Marriage Act.',
                'body' => <<<'HTML'
<p>A divorce by mutual consent is possible when both husband and wife agree that the marriage should end. For Hindus, Buddhists, Jains and Sikhs it is governed by <strong>Section 13B of the Hindu Marriage Act, 1955</strong>. Similar provisions exist under the Special Marriage Act, 1954.</p>
<h2>Who can apply</h2>
<ul><li>Both spouses must agree freely, without pressure.</li><li>They must have been living separately for at least <strong>one year</strong> before filing.</li><li>They must agree that they cannot live together.</li></ul>
<h2>The two motions</h2>
<p>The couple files a joint petition in the family court. This is the <strong>first motion</strong>, where the court records both statements. The law then provides a waiting period of six months before the <strong>second motion</strong>, when both spouses confirm they still want the divorce.</p>
<p>The Supreme Court has held that this waiting period is not mandatory in every case. A court may waive it in suitable cases, for example where the spouses have already lived apart for a long time and settled all issues.</p>
<h2>What to settle in advance</h2>
<ol><li>Custody of and access to children</li><li>Maintenance or a one-time settlement amount</li><li>Return of jewellery, gifts and other belongings</li><li>Withdrawal of any pending cases between the spouses</li></ol>
<p>A clear written settlement makes the process smoother and avoids disputes later. If you are considering a mutual consent divorce, our family law team can advise you on your situation.</p>
HTML,
            ],
            [
                'title' => 'How to file a consumer complaint online',
                'slug' => 'how-to-file-consumer-complaint-online',
                'category' => 'consumer-rights',
                'status' => PostStatus::Draft,
                'excerpt' => 'Faulty product or poor service? Under the Consumer Protection Act, 2019 you can file a complaint online through the e-Daakhil portal.',
                'body' => <<<'HTML'
<p>The <strong>Consumer Protection Act, 2019</strong> lets you complain about defective goods, deficient services and unfair trade practices. Complaints can now be filed online.</p>
<h2>Which commission?</h2>
<p>Where you file depends on the value of what you paid. Smaller claims go to the District Commission, larger ones to the State Commission, and the largest to the National Commission.</p>
<h2>Time limit</h2>
<p>A complaint should normally be filed within <strong>two years</strong> of the problem arising.</p>
<p>This draft is still being written.</p>
HTML,
            ],
        ];
    }
}
