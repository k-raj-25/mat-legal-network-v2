<?php

namespace App\Enums;

enum LawyerStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Approved => 'Approved',
            self::Rejected => 'Changes requested',
            self::Suspended => 'Suspended',
        };
    }

    /** Tailwind classes for status badges. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Approved => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::Rejected => 'bg-rose-50 text-rose-800 ring-rose-200',
            self::Suspended => 'bg-slate-100 text-slate-700 ring-slate-300',
        };
    }
}
