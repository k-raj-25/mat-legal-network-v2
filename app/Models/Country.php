<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'iso2', 'phone_code', 'currency', 'bar_body_label', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public static function default(): self
    {
        return once(fn () => static::where('iso2', config('mat.default_country'))->firstOrFail());
    }
}
