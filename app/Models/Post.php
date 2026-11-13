<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    public const PER_PAGE = 9;

    /** Same deep-pagination cap as the lawyer directory (anti-scraping, cheap queries). */
    public const MAX_PAGE = 100;

    /** Columns needed for cards on the public index (no body). */
    public const CARD_COLUMNS = ['id', 'user_id', 'blog_category_id', 'title', 'slug', 'excerpt', 'featured_image_path', 'featured_image_alt', 'published_at', 'reading_minutes'];

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'blog_category_id', 'featured_image_alt', 'meta_title', 'meta_description'];

    protected $attributes = ['status' => 'draft', 'reading_minutes' => 1];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            $words = str_word_count(strip_tags((string) $post->body));
            $post->reading_minutes = max(1, (int) ceil($words / 200));
        });
        // The sitemap lists published posts.
        static::saved(fn () => Cache::forget('sitemap.xml'));
        static::deleted(function (Post $post) {
            Cache::forget('sitemap.xml');
            if ($post->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('posts.status', PostStatus::Published->value)->whereNotNull('posts.published_at');
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published;
    }

    public function imageUrl(): ?string
    {
        return $this->featured_image_path ? Storage::disk('public')->url($this->featured_image_path) : null;
    }

    /** Illustrated cover shown when a post has no uploaded image: one per seeded category, plus a general one. */
    public function coverArtUrl(): string
    {
        $slug = $this->category?->slug;
        $name = $slug && is_file(public_path("images/illustrations/blog-{$slug}.svg")) ? "blog-{$slug}" : 'blog-default';

        return asset("images/illustrations/{$name}.svg");
    }

    /** Excerpt for cards and meta tags: the written one, or the start of the article. */
    public function summary(int $limit = 160): string
    {
        $text = $this->excerpt ?: html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>', '</h2>', '</h3>'], ' ', (string) $this->body)), ENT_QUOTES | ENT_HTML5);

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), $limit);
    }

    public function authorName(): string
    {
        return $this->author?->name ?? 'MAT LEGAL NETWORK';
    }

    /** A unique slug based on $source, ignoring this post's own row. */
    public static function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($source), 160, '') ?: 'post';
        $base = rtrim($base, '-');
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
