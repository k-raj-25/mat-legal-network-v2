<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Admin-only: blog categories editors can file posts under. */
class BlogCategoryController extends Controller
{
    public function index()
    {
        return view('admin.posts.categories', [
            'categories' => BlogCategory::withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('blog_categories', 'name')]]);
        $slug = Str::slug($data['name']) ?: 'category';
        if (BlogCategory::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A category with a very similar name already exists.']);
        }
        $cat = BlogCategory::create(['name' => $data['name'], 'slug' => $slug]);
        AuditLog::record('blog_category.created', $cat, ['name' => $cat->name]);

        return back()->with('status', 'Category "'.$cat->name.'" added.');
    }

    public function toggle(BlogCategory $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();
        AuditLog::record('blog_category.toggled', $category, ['name' => $category->name, 'active' => $category->is_active]);

        return back()->with('status', $category->name.' is now '.($category->is_active ? 'shown' : 'hidden').'.');
    }
}
