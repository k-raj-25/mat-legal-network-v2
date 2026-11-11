<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Court extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('taxonomy.'.static::class));
        static::deleted(fn () => Cache::forget('taxonomy.'.static::class));
    }

    public function lawyers(): BelongsToMany
    {
        return $this->belongsToMany(LawyerProfile::class, 'court_lawyer');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Active items for dropdowns and filters, cached as plain arrays
     * (the cache store is configured to refuse unserializing objects).
     *
     * @return Collection<int, object{id:int, name:string, slug:string}>
     */
    public static function options()
    {
        $rows = Cache::remember('taxonomy.'.static::class, 3600, fn () => static::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug'])->toArray());

        return collect($rows)->map(fn ($r) => (object) $r);
    }
}
