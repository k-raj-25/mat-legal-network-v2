<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request, ?BlogCategory $category = null)
    {
        abort_if($category && ! $category->is_active, 404);

        $page = (int) $request->query('page', 1);
        abort_if($page > Post::MAX_PAGE, 404);

        $posts = Post::query()->published()
            ->select(Post::CARD_COLUMNS)
            ->with(['author:id,name', 'category:id,name,slug'])
            ->when($category, fn ($q) => $q->where('blog_category_id', $category->id))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(Post::PER_PAGE)->withQueryString();

        abort_if($page > 1 && $posts->isEmpty(), 404);

        return view('blog.index', [
            'posts' => $posts,
            'category' => $category,
            'categories' => BlogCategory::options(),
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->isPublished() && $post->published_at, 404);
        $post->load(['author:id,name', 'category:id,name,slug,is_active']);

        $related = Post::query()->published()->select(Post::CARD_COLUMNS)
            ->with(['author:id,name', 'category:id,name,slug'])
            ->whereKeyNot($post->id)
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->orderByDesc('published_at')->limit(3)->get();

        return view('blog.show', ['post' => $post, 'related' => $related, 'preview' => false]);
    }
}
