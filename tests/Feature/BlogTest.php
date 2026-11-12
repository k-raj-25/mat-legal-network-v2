<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\BlogCategory;
use App\Models\Post;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->editor()->create(['name' => 'Neha Author']);
    }

    private function article(array $attrs = [], ?User $author = null): Post
    {
        $author ??= $this->editor();
        $post = new Post(array_merge(['title' => 'Default article title', 'body' => '<p>Some helpful body text here.</p>'], $attrs));
        $post->slug = Post::uniqueSlug($attrs['slug'] ?? $post->title);
        $post->user_id = $author->id;
        $post->status = $attrs['status'] ?? PostStatus::Published;
        $post->published_at = $attrs['published_at'] ?? ($post->status === PostStatus::Published ? now() : null);
        $post->save();

        return $post;
    }

    public function test_blog_index_lists_only_published_posts(): void
    {
        $this->article(['title' => 'Published guide to bail']);
        $this->article(['title' => 'Secret draft article', 'status' => PostStatus::Draft]);

        $this->get('/blog')->assertOk()
            ->assertSee('Published guide to bail')->assertDontSee('Secret draft article')
            ->assertSee('<link rel="canonical" href="'.route('blog.index').'">', false)
            ->assertSee('Neha Author');
    }

    public function test_blog_index_paginates_newest_first_and_caps_deep_pages(): void
    {
        $author = $this->editor();
        foreach (range(1, Post::PER_PAGE + 2) as $i) {
            $this->article(['title' => "Article number $i", 'published_at' => now()->subDays(100 - $i)], $author);
        }
        $first = $this->get('/blog')->assertOk()->getContent();
        $this->assertLessThan(strpos($first, 'Article number 3<'), strpos($first, 'Article number 11<'), 'newest first');
        $this->assertStringNotContainsString('Article number 2<', $first);
        $this->get('/blog?page=2')->assertOk()->assertSee('Article number 2<', false)->assertSee('rel="prev"', false);
        $this->get('/blog?page=3')->assertNotFound();
        $this->get('/blog?page='.(Post::MAX_PAGE + 1))->assertNotFound();
    }

    public function test_empty_blog_shows_a_friendly_message(): void
    {
        $this->get('/blog')->assertOk()->assertSee('No articles here yet');
    }

    public function test_post_page_shows_published_post_with_seo_tags(): void
    {
        $cat = BlogCategory::where('slug', 'criminal-law')->first();
        $post = $this->article(['title' => 'How anticipatory bail works', 'excerpt' => 'A short guide to anticipatory bail.', 'blog_category_id' => $cat->id,
            'meta_description' => 'Learn how anticipatory bail works in India.', 'body' => '<h2>What it is</h2><p>Explained simply.</p>']);

        $res = $this->get('/blog/'.$post->slug)->assertOk()
            ->assertSee('<h1 class="mt-4 text-4xl leading-[1.08] break-words sm:text-5xl" data-testid="post-title">How anticipatory bail works</h1>', false)
            ->assertSee('<h2>What it is</h2>', false)
            ->assertSee('<meta name="description" content="Learn how anticipatory bail works in India.">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<link rel="canonical" href="'.route('blog.show', $post->slug).'">', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('Criminal law');

        // Disclaimer wording matches the site: the firm DOES give legal advice when engaged.
        $html = $res->getContent();
        $this->assertStringContainsString('is a law firm and gives legal advice when you engage it for your matter', $html);
        $this->assertStringNotContainsString('does not give legal advice', $html);
        $this->assertStringNotContainsString('do not provide legal advice', $html);
    }

    public function test_drafts_and_unknown_posts_are_not_public(): void
    {
        $draft = $this->article(['status' => PostStatus::Draft]);
        $this->get('/blog/'.$draft->slug)->assertNotFound();
        $this->get('/blog/no-such-post')->assertNotFound();
    }

    public function test_category_page_filters_and_hidden_categories_404(): void
    {
        $family = BlogCategory::where('slug', 'family-divorce')->first();
        $crime = BlogCategory::where('slug', 'criminal-law')->first();
        $this->article(['title' => 'Custody basics explained', 'blog_category_id' => $family->id]);
        $this->article(['title' => 'Arrest rights explained', 'blog_category_id' => $crime->id]);

        $this->get('/blog/category/family-divorce')->assertOk()->assertSee('Custody basics explained')->assertDontSee('Arrest rights explained')
            ->assertSee('aria-current="page"', false);
        $family->update(['is_active' => false]);
        $this->get('/blog/category/family-divorce')->assertNotFound();
        $this->get('/blog/category/nope')->assertNotFound();
    }

    public function test_html_is_sanitised_on_save(): void
    {
        $this->actingAs($editor = $this->editor());
        $dirty = '<h1>Big</h1><p onclick="alert(1)" style="color:red">Hi <script>alert(1)</script><a href="javascript:alert(1)">bad</a> <a href="https://example.com" onmouseover="x()">good</a></p>'
            .'<img src=x onerror=alert(1)><iframe src="https://evil.test"></iframe><div>Line from a div</div><ul><li><strong>ok</strong></li></ul><style>body{}</style>';
        $this->post('/admin/posts', ['title' => 'Sanitised article', 'body' => $dirty, 'action' => 'publish'])->assertRedirect();

        $body = Post::where('title', 'Sanitised article')->value('body');
        foreach (['<script', 'onclick', 'onerror', 'onmouseover', 'javascript:', '<iframe', '<img', 'style=', '<style', '<h1', '<div'] as $bad) {
            $this->assertStringNotContainsStringIgnoringCase($bad, $body);
        }
        foreach (['<h2>Big</h2>', 'href="https://example.com"', '<strong>ok</strong>', '<p>Line from a div</p>'] as $good) {
            $this->assertStringContainsString($good, $body);
        }
        $this->get('/blog/sanitised-article')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_sanitizer_unit_cases(): void
    {
        $this->assertSame('', HtmlSanitizer::clean('<p> </p><p><br></p>'));
        $this->assertTrue(HtmlSanitizer::isEmpty('<p>&nbsp;</p>'));
        $this->assertStringContainsString('<a href="mailto:a@b.co">mail</a>', HtmlSanitizer::clean('<a href="mailto:a@b.co">mail</a>'));
        $this->assertStringNotContainsString('data:', HtmlSanitizer::clean('<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>'));
    }

    public function test_publish_draft_unpublish_flow(): void
    {
        $this->actingAs($this->editor());
        $this->post('/admin/posts', ['title' => 'Workflow article', 'body' => '', 'action' => 'publish'])->assertSessionHasErrors('body');
        $this->post('/admin/posts', ['title' => 'Workflow article', 'body' => '', 'action' => 'save'])->assertSessionHasNoErrors();
        $post = Post::where('title', 'Workflow article')->firstOrFail();
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->get('/blog/'.$post->slug)->assertNotFound();

        $this->put(route('admin.posts.update', $post), ['title' => 'Workflow article', 'body' => '<p>Now written.</p>', 'action' => 'publish'])->assertRedirect();
        $post->refresh();
        $this->assertTrue($post->isPublished());
        $firstPublished = $post->published_at;
        $this->get('/blog/'.$post->slug)->assertOk();

        $this->put(route('admin.posts.update', $post), ['title' => 'Workflow article', 'body' => '<p>Now written.</p>', 'action' => 'unpublish'])->assertRedirect();
        $this->assertFalse($post->fresh()->isPublished());
        $this->get('/blog/'.$post->slug)->assertNotFound();

        $this->travel(2)->days();
        $this->put(route('admin.posts.update', $post), ['title' => 'Workflow article', 'body' => '<p>Now written.</p>', 'action' => 'publish']);
        $this->assertEquals($firstPublished->timestamp, $post->fresh()->published_at->timestamp, 're-publishing keeps the original date');

        // Saving a live post with an empty body is refused (it would publish an empty page).
        $this->put(route('admin.posts.update', $post), ['title' => 'Workflow article', 'body' => '<p></p>', 'action' => 'save'])->assertSessionHasErrors('body');
    }

    public function test_slugs_are_unique_and_validated(): void
    {
        $this->actingAs($this->editor());
        $this->post('/admin/posts', ['title' => 'Same title here', 'body' => '<p>x</p>', 'action' => 'save']);
        $this->post('/admin/posts', ['title' => 'Same title here', 'body' => '<p>x</p>', 'action' => 'save']);
        $this->assertEqualsCanonicalizing(['same-title-here', 'same-title-here-2'], Post::pluck('slug')->all());

        $this->post('/admin/posts', ['title' => 'Another title', 'slug' => 'same-title-here', 'body' => '<p>x</p>', 'action' => 'save'])->assertSessionHasErrors('slug');
        $this->post('/admin/posts', ['title' => 'Another title', 'slug' => 'Bad Slug!', 'body' => '<p>x</p>', 'action' => 'save'])->assertSessionHasErrors('slug');
        $this->post('/admin/posts', ['title' => 'Another title', 'slug' => 'custom-address', 'body' => '<p>x</p>', 'action' => 'save'])->assertSessionHasNoErrors();
        $this->assertTrue(Post::where('slug', 'custom-address')->exists());
    }

    public function test_featured_image_upload_replace_and_delete(): void
    {
        Storage::fake('public');
        $this->actingAs($this->editor());

        $this->post('/admin/posts', ['title' => 'Image article', 'body' => '<p>x</p>', 'action' => 'save', 'featured_image' => UploadedFile::fake()->image('a.jpg', 1600, 900)])
            ->assertSessionHasErrors('featured_image_alt');
        $this->post('/admin/posts', ['title' => 'Image article', 'body' => '<p>x</p>', 'action' => 'publish', 'featured_image' => UploadedFile::fake()->image('a.jpg', 1600, 900), 'featured_image_alt' => 'A courtroom'])
            ->assertSessionHasNoErrors();
        $post = Post::where('title', 'Image article')->firstOrFail();
        Storage::disk('public')->assertExists($first = $post->featured_image_path);
        $this->get('/blog/'.$post->slug)->assertSee('alt="A courtroom"', false)->assertSee('<meta property="og:image"', false);

        $this->put(route('admin.posts.update', $post), ['title' => 'Image article', 'body' => '<p>x</p>', 'action' => 'save', 'featured_image' => UploadedFile::fake()->image('b.png', 1200, 675), 'featured_image_alt' => 'A gavel']);
        $second = $post->fresh()->featured_image_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->post('/admin/posts', ['title' => 'Bad file', 'body' => '<p>x</p>', 'action' => 'save', 'featured_image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'), 'featured_image_alt' => 'x'])
            ->assertSessionHasErrors('featured_image');
        $this->post('/admin/posts', ['title' => 'Too big', 'body' => '<p>x</p>', 'action' => 'save', 'featured_image' => UploadedFile::fake()->image('big.jpg')->size(5000), 'featured_image_alt' => 'x'])
            ->assertSessionHasErrors('featured_image');

        $this->put(route('admin.posts.update', $post), ['title' => 'Image article', 'body' => '<p>x</p>', 'action' => 'save', 'remove_image' => '1']);
        $this->assertNull($post->fresh()->featured_image_path);
        Storage::disk('public')->assertMissing($second);

        $this->put(route('admin.posts.update', $post), ['title' => 'Image article', 'body' => '<p>x</p>', 'action' => 'save', 'featured_image' => UploadedFile::fake()->image('c.jpg'), 'featured_image_alt' => 'C']);
        $third = $post->fresh()->featured_image_path;
        $this->delete(route('admin.posts.destroy', $post));
        Storage::disk('public')->assertMissing($third);
    }

    public function test_preview_shows_draft_to_its_author_without_indexing(): void
    {
        $editor = $this->editor();
        $draft = $this->article(['title' => 'Draft for preview', 'status' => PostStatus::Draft], $editor);
        $this->actingAs($editor)->get(route('admin.posts.preview', $draft))->assertOk()
            ->assertSee('Draft for preview')->assertSee('this draft is not on the blog yet')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_sitemap_and_navigation_include_the_blog(): void
    {
        Cache::forget('sitemap.xml');
        $live = $this->article(['title' => 'Sitemap article']);
        $draft = $this->article(['title' => 'Hidden from sitemap', 'status' => PostStatus::Draft]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>'.route('blog.index').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('blog.show', $live->slug).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('blog.category', 'family-divorce').'</loc>', $xml);
        $this->assertStringNotContainsString($draft->slug, $xml);

        // Publishing clears the cached sitemap.
        $this->article(['title' => 'Fresh article']);
        $this->assertStringContainsString('fresh-article', $this->get('/sitemap.xml')->getContent());

        $this->get('/')->assertOk()->assertSee('href="'.route('blog.index').'"', false);
    }

    public function test_index_query_count_does_not_grow_with_posts(): void
    {
        $author = $this->editor();
        $cats = BlogCategory::pluck('id')->all();
        $rows = [];
        foreach (range(1, 3000) as $i) {
            $rows[] = ['user_id' => $author->id, 'blog_category_id' => $cats[$i % count($cats)], 'title' => "Bulk $i", 'slug' => "bulk-$i", 'body' => '<p>b</p>',
                'status' => 'published', 'published_at' => now()->subMinutes($i), 'reading_minutes' => 1, 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('posts')->insert($chunk);
        }

        DB::enableQueryLog();
        $this->get('/blog?page=50')->assertOk()->assertSee('Bulk 442');
        $public = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->get('/blog/category/'.BlogCategory::first()->slug)->assertOk();
        $category = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->actingAs($this->admin())->get('/admin/posts?page=20')->assertOk();
        $panel = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Constant number of queries (count + page + eager loads), never one per post.
        $this->assertLessThan(10, $public, 'public index queries');
        $this->assertLessThan(10, $category, 'category queries');
        $this->assertLessThan(16, $panel, 'admin list queries');
    }
}
