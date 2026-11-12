<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\BlogCategory;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Blog posts in the panel. Shared by the admin (all posts) and editors (only their own posts);
 * PostPolicy decides what each may see and change.
 */
class PostController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Post::class);
        $user = $request->user();
        $all = $user->can('viewAll', Post::class);

        $status = $request->query('status', 'all');
        if (! in_array($status, ['all', 'draft', 'published'], true)) {
            $status = 'all';
        }

        $scope = Post::query()->when(! $all, fn ($q) => $q->where('user_id', $user->id));
        $author = $all ? (int) $request->query('author') : 0;
        if ($author) {
            $scope->where('user_id', $author);
        }

        $q = (clone $scope)->with(['author:id,name', 'category:id,name'])
            ->select(['id', 'user_id', 'blog_category_id', 'title', 'slug', 'status', 'published_at', 'updated_at']);
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where('title', 'like', '%'.addcslashes(mb_substr($term, 0, 100), '%_\\').'%');
        }

        return view('admin.posts.index', [
            'posts' => $q->orderByDesc('updated_at')->orderByDesc('id')->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => (clone $scope)->toBase()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'showAll' => $all,
            'author' => $author,
            'authors' => $all ? User::whereIn('role', [Role::Admin->value, Role::Editor->value])->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Post::class);

        return view('admin.posts.form', ['post' => new Post, 'categories' => BlogCategory::options()]);
    }

    public function store(PostRequest $request, PostService $service)
    {
        Gate::authorize('create', Post::class);
        $post = $service->save(new Post, $request->validated(), $request->file('featured_image'), $request->user());

        return redirect()->route('admin.posts.edit', $post)->with('status', $post->isPublished() ? 'Post published.' : 'Draft saved.');
    }

    public function edit(Post $post)
    {
        Gate::authorize('update', $post);
        $post->load('author:id,name');

        return view('admin.posts.form', ['post' => $post, 'categories' => $this->categoriesFor($post)]);
    }

    public function update(PostRequest $request, Post $post, PostService $service)
    {
        Gate::authorize('update', $post);
        if (in_array($request->input('action'), ['publish', 'unpublish'], true)) {
            Gate::authorize('publish', $post);
        }
        $wasPublished = $post->isPublished();
        $service->save($post, $request->validated(), $request->file('featured_image'), $request->user());

        $message = match (true) {
            ! $wasPublished && $post->isPublished() => 'Post published. It is now live on the blog.',
            $wasPublished && ! $post->isPublished() => 'Post unpublished. It is now a draft and hidden from the blog.',
            default => 'Changes saved.',
        };

        return redirect()->route('admin.posts.edit', $post)->with('status', $message);
    }

    public function destroy(Post $post, PostService $service)
    {
        Gate::authorize('delete', $post);
        $title = $post->title;
        $service->delete($post);

        return redirect()->route('admin.posts.index')->with('status', '"'.$title.'" was deleted.');
    }

    /** Draft (or live) post rendered with the public template, for its author or the admin. */
    public function preview(Post $post)
    {
        Gate::authorize('view', $post);
        $post->load(['author:id,name', 'category:id,name,slug']);

        return response()->view('blog.show', ['post' => $post, 'related' => collect(), 'preview' => true])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Active categories, plus the post's current one even if it was switched off since. */
    private function categoriesFor(Post $post)
    {
        $options = BlogCategory::options();
        if ($post->blog_category_id && ! $options->contains('id', $post->blog_category_id) && $c = BlogCategory::find($post->blog_category_id)) {
            $options->push((object) ['id' => $c->id, 'name' => $c->name.' (hidden)', 'slug' => $c->slug]);
        }

        return $options;
    }
}
