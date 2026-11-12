<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Assigned = 'assigned';
    case Closed = 'closed';
    case SuspectedSpam = 'suspected_spam';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::SuspectedSpam => 'Suspected spam',
            default => ucfirst($this->value),
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-sky-50 text-sky-800 ring-sky-200',
            self::Contacted => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
            self::Assigned => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::Closed => 'bg-slate-100 text-slate-700 ring-slate-300',
            self::SuspectedSpam => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Spam => 'bg-rose-50 text-rose-800 ring-rose-200',
        };
    }

    /** Statuses an admin can pick by hand. "Suspected spam" is only ever set by the AI check. */
    public static function manual(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s !== self::SuspectedSpam));
    }
}
