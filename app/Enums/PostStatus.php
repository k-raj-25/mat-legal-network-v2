<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }

    /** Tailwind classes for status badges (AA contrast on the tinted background). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-300',
            self::Published => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        };
    }
}
