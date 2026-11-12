<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BlogCategory extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('blog.categories'));
        static::deleted(fn () => Cache::forget('blog.categories'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Active categories for menus and dropdowns, cached as plain arrays.
     *
     * @return Collection<int, object{id:int, name:string, slug:string}>
     */
    public static function options()
    {
        return collect(Cache::remember('blog.categories', 3600, fn () => static::active()->orderBy('name')->get(['id', 'name', 'slug'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->all()))
            ->map(fn ($c) => (object) $c);
    }
}
