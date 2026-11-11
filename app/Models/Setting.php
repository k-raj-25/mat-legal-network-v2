<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** All settings merged over config defaults, cached. */
    public static function allValues(): array
    {
        return Cache::rememberForever('settings.all', function () {
            $stored = static::query()->pluck('value', 'key')->all();

            return array_merge(config('mat.settings_defaults'), array_filter($stored, fn ($v) => $v !== null && $v !== ''));
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::allValues()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings.all');
    }
}
