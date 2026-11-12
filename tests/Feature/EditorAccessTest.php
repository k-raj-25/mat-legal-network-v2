<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Enums\LawyerStatus;
use App\Enums\PostStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\BlogCategory;
use App\Models\Enquiry;
use App\Models\Post;
use App\Models\PracticeArea;
use App\Models\ProfileChangeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Editors (blog authors) may manage ONLY their own blog posts and their own password.
 * Every other admin route must refuse them, and the admin manages editor accounts.
 */
class EditorAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Admin-panel routes an editor is allowed to use. Everything else under admin.* must be 403. */
    private const EDITOR_ROUTES = [
        'admin.posts.index', 'admin.posts.create', 'admin.posts.store', 'admin.posts.edit',
        'admin.posts.preview', 'admin.posts.update', 'admin.posts.destroy',
        'admin.account.edit', 'admin.account.password',
    ];

    private function editor(array $attrs = []): User
    {
        return User::factory()->editor()->create($attrs);
    }

    private function article(User $author, array $attrs = []): Post
    {
        $post = new Post(array_merge(['title' => 'A post by '.$author->name, 'body' => '<p>Hello readers, this is the body.</p>'], $attrs));
        $post->slug = Post::uniqueSlug($attrs['slug'] ?? $post->title);
        $post->user_id = $author->id;
        $post->status = $attrs['status'] ?? PostStatus::Draft;
        $post->published_at = $post->status === PostStatus::Published ? now() : null;
        $post->save();

        return $post;
    }

    public function test_editor_logs_in_to_the_blog_area(): void
    {
        $this->editor(['email' => 'writer@example.com', 'password' => 'Secret123']);

        $this->post('/login', ['email' => 'writer@example.com', 'password' => 'Secret123'])->assertRedirect('/admin/posts');
        $this->get('/admin/posts')->assertOk()->assertSee('My blog posts');
    }

    public function test_editor_is_refused_on_every_other_admin_route(): void
    {
        // Real records so route-model binding resolves and only authorisation can refuse the request.
        $lawyer = $this->lawyer(LawyerStatus::Pending);
        $doc = $lawyer->documents()->create(['type' => 'id_proof', 'path' => 'x.pdf', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1]);
        $enquiry = Enquiry::create(['name' => 'T', 'phone' => '9811122233', 'message' => 'Need a lawyer please', 'preferred_contact' => 'call', 'consented_at' => now(), 'status' => EnquiryStatus::New]);
        $log = ProfileChangeLog::create(['lawyer_profile_id' => $lawyer->id, 'user_id' => $lawyer->user_id, 'changes' => ['bio' => ['old' => 'a', 'new' => 'b']]]);
        $otherEditor = $this->editor();
        $category = BlogCategory::first();
        $params = [
            'lawyer' => $lawyer->slug, 'document' => $doc->id, 'enquiry' => $enquiry->id, 'log' => $log->id,
            'type' => 'practice-areas', 'id' => PracticeArea::first()->id, 'editor' => $otherEditor->id, 'category' => $category->slug,
        ];

        $editor = $this->editor();
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'admin.') || in_array($name, self::EDITOR_ROUTES, true)) {
                continue;
            }
            $uri = '/'.preg_replace_callback('/\{(\w+)(?::\w+)?\??\}/', fn ($m) => $params[$m[1]] ?? 'missing', $route->uri());
            $this->assertStringNotContainsString('missing', $uri, "No test value for a parameter of $name");
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $this->actingAs($editor)->call($method, $uri)->assertForbidden();
                $checked++;
            }
        }
        $this->assertGreaterThan(25, $checked, 'expected to check every admin-only route');

        // Nothing was changed by the refused requests.
        $this->assertSame(LawyerStatus::Pending, $lawyer->fresh()->status);
        $this->assertTrue($otherEditor->fresh()->is_active);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_editor_panel_shows_only_blog_navigation(): void
    {
        $this->lawyer(LawyerStatus::Pending);
        $html = $this->actingAs($this->editor())->get('/admin/posts')->assertOk()->getContent();

        foreach (['Lawyers', 'Enquiries', 'Profile changes', 'Lists &amp; filters', 'Settings', 'Audit log', 'Blog editors', 'Blog categories', 'Dashboard'] as $label) {
            $this->assertStringNotContainsString('</svg> '.$label, $html, "editor sees the \"$label\" menu item");
        }
        $this->assertStringContainsString('My blog posts', $html);
        $this->assertStringContainsString('My account', $html);
        $this->assertStringNotContainsString(route('admin.lawyers.index'), $html);
    }

    public function test_editor_sees_and_changes_only_their_own_posts(): void
    {
        $me = $this->editor();
        $other = $this->editor();
        $admin = $this->admin();
        $mine = $this->article($me, ['title' => 'My own article']);
        $theirs = $this->article($other, ['title' => 'Someone elses article', 'status' => PostStatus::Published]);
        $adminPost = $this->article($admin, ['title' => 'The admin article']);

        $this->actingAs($me);
        $this->get('/admin/posts')->assertOk()->assertSee('My own article')->assertDontSee('Someone elses article')->assertDontSee('The admin article');
        // The author filter is ignored for editors.
        $this->get('/admin/posts?author='.$other->id)->assertOk()->assertSee('My own article')->assertDontSee('Someone elses article');

        foreach ([$theirs, $adminPost] as $p) {
            $this->get(route('admin.posts.edit', $p))->assertForbidden();
            $this->get(route('admin.posts.preview', $p))->assertForbidden();
            $this->put(route('admin.posts.update', $p), ['title' => 'Hijacked title', 'body' => '<p>x</p>', 'action' => 'unpublish'])->assertForbidden();
            $this->delete(route('admin.posts.destroy', $p))->assertForbidden();
        }
        $this->assertSame('Someone elses article', $theirs->fresh()->title);
        $this->assertTrue($theirs->fresh()->isPublished());
        $this->assertNotNull($adminPost->fresh());

        $this->get(route('admin.posts.edit', $mine))->assertOk();
        $this->put(route('admin.posts.update', $mine), ['title' => 'My own article, edited', 'body' => '<p>New body text.</p>', 'action' => 'publish'])->assertRedirect();
        $this->assertTrue($mine->fresh()->isPublished());
        $this->delete(route('admin.posts.destroy', $mine))->assertRedirect('/admin/posts');
        $this->assertNull($mine->fresh());
    }

    public function test_new_posts_belong_to_the_editor_who_wrote_them(): void
    {
        $me = $this->editor();
        $other = $this->editor();
        $this->actingAs($me)->post('/admin/posts', ['title' => 'Written by me', 'body' => '<p>Text</p>', 'action' => 'save', 'user_id' => $other->id])->assertRedirect();
        $this->assertSame($me->id, Post::where('title', 'Written by me')->value('user_id'));
    }

    public function test_admin_manages_every_post(): void
    {
        $editor = $this->editor(['name' => 'Asha Writer']);
        $post = $this->article($editor, ['title' => 'Editor article', 'status' => PostStatus::Published]);
        $this->actingAs($this->admin());

        $this->get('/admin/posts')->assertOk()->assertSee('Editor article')->assertSee('Asha Writer');
        $this->get('/admin/posts?author='.$editor->id)->assertOk()->assertSee('Editor article');
        $this->get(route('admin.posts.edit', $post))->assertOk()->assertSee('Written by Asha Writer');
        $this->put(route('admin.posts.update', $post), ['title' => 'Editor article', 'body' => '<p>Body</p>', 'action' => 'unpublish'])->assertRedirect();
        $this->assertFalse($post->fresh()->isPublished());
        $this->assertSame($editor->id, $post->fresh()->user_id, 'admin edits keep the original author');
        $this->delete(route('admin.posts.destroy', $post))->assertRedirect();
        $this->assertNull($post->fresh());
    }

    public function test_lawyers_and_guests_cannot_reach_blog_management(): void
    {
        $lawyer = $this->lawyer()->user;
        $post = $this->article($this->editor());
        foreach (['/admin/posts', '/admin/posts/create', route('admin.posts.edit', $post, false), '/admin/account'] as $url) {
            $this->get($url)->assertRedirect('/login');
            $this->actingAs($lawyer)->get($url)->assertForbidden();
            auth()->logout();
        }
        $this->actingAs($lawyer)->post('/admin/posts', ['title' => 'Lawyer post', 'body' => '<p>x</p>', 'action' => 'publish'])->assertForbidden();
        $this->assertSame(0, Post::where('title', 'Lawyer post')->count());
    }

    public function test_editor_cannot_use_the_lawyer_portal(): void
    {
        $this->actingAs($this->editor())->get('/lawyer')->assertForbidden();
    }

    public function test_admin_creates_an_editor_who_can_sign_in(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/editors')->assertOk();
        $this->get('/admin/editors/create')->assertOk();

        $this->post('/admin/editors', ['name' => 'Ravi Author', 'email' => 'Ravi@Example.com ', 'password' => 'Writer123', 'password_confirmation' => 'Writer123'])->assertRedirect();
        $editor = User::where('email', 'ravi@example.com')->firstOrFail();
        $this->assertSame(Role::Editor, $editor->role);
        $this->assertTrue($editor->is_active);
        $this->assertTrue(AuditLog::where('action', 'editor.created')->exists());

        $this->get('/admin/editors')->assertSee('Ravi Author');
        $this->post('/logout');
        $this->post('/login', ['email' => 'ravi@example.com', 'password' => 'Writer123'])->assertRedirect('/admin/posts');
    }

    public function test_editor_account_validation(): void
    {
        $this->editor(['email' => 'taken@example.com']);
        $this->actingAs($this->admin());
        $this->post('/admin/editors', ['name' => '', 'email' => 'taken@example.com', 'password' => 'short', 'password_confirmation' => 'nope'])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_admin_edits_resets_password_and_deactivates_an_editor(): void
    {
        $editor = $this->editor(['email' => 'e@example.com', 'password' => 'OldPass123']);
        $this->actingAs($this->admin());

        $this->get(route('admin.editors.edit', $editor))->assertOk();
        $this->put(route('admin.editors.update', $editor), ['name' => 'Renamed Editor', 'email' => 'new.e@example.com'])->assertRedirect();
        $this->assertSame('Renamed Editor', $editor->fresh()->name);
        $this->assertSame('new.e@example.com', $editor->fresh()->email);

        $oldToken = $editor->fresh()->remember_token;
        $this->put(route('admin.editors.password', $editor), ['password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertRedirect();
        $this->assertTrue(Hash::check('NewPass123', $editor->fresh()->password));
        $this->assertNotSame($oldToken, $editor->fresh()->remember_token, 'remember-me cookies are invalidated');
        $this->assertTrue(AuditLog::where('action', 'editor.password_reset')->exists());

        $this->post(route('admin.editors.toggle', $editor))->assertRedirect();
        $this->assertFalse($editor->fresh()->is_active);
        $this->post('/logout');
        $this->post('/login', ['email' => 'new.e@example.com', 'password' => 'NewPass123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($this->admin())->post(route('admin.editors.toggle', $editor))->assertRedirect();
        $this->assertTrue($editor->fresh()->is_active);
    }

    public function test_deactivated_editor_is_signed_out_on_next_request(): void
    {
        $editor = $this->editor(['is_active' => false]);
        $this->actingAs($editor)->get('/admin/posts')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_editor_management_only_targets_editor_accounts(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $lawyerUser = $this->lawyer()->user;
        $this->actingAs($admin);
        foreach ([$otherAdmin, $lawyerUser] as $u) {
            $this->get(route('admin.editors.edit', $u))->assertForbidden();
            $this->put(route('admin.editors.password', $u), ['password' => 'Hacked1234', 'password_confirmation' => 'Hacked1234'])->assertForbidden();
            $this->post(route('admin.editors.toggle', $u))->assertForbidden();
            $this->assertTrue($u->fresh()->is_active);
        }
    }

    public function test_editor_changes_own_password(): void
    {
        $editor = $this->editor(['password' => 'OldPass123']);
        $this->actingAs($editor);
        $this->get('/admin/account')->assertOk()->assertSee($editor->email);
        $this->put('/admin/account/password', ['current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertSessionHasErrors('current_password');
        $this->put('/admin/account/password', ['current_password' => 'OldPass123', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPass123', $editor->fresh()->password));
    }

    public function test_admin_blog_pages_render(): void
    {
        $editor = $this->editor();
        $post = $this->article($editor);
        $this->actingAs($this->admin());
        foreach (['/admin/posts', '/admin/posts?status=draft', '/admin/posts?status=published', '/admin/posts?status=bogus', '/admin/posts?q=post',
            '/admin/posts/create', route('admin.posts.edit', $post, false), route('admin.posts.preview', $post, false),
            '/admin/blog-categories', '/admin/editors', '/admin/editors?q=x', route('admin.editors.edit', $editor, false), '/admin/account', '/admin/audit-log'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin')->assertOk()->assertSee('Blog posts')->assertSee('Blog editors');
    }

    public function test_admin_manages_blog_categories(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/blog-categories', ['name' => 'Tax & GST'])->assertRedirect();
        $cat = BlogCategory::where('slug', 'tax-gst')->firstOrFail();
        $this->post('/admin/blog-categories', ['name' => 'Tax GST'])->assertSessionHasErrors('name');
        $this->post(route('admin.blog-categories.toggle', $cat))->assertRedirect();
        $this->assertFalse($cat->fresh()->is_active);
    }
}
