<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Lawyer = 'lawyer';
    /** Blog editor / author: can only manage blog posts. */
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Lawyer => 'Lawyer',
            self::Editor => 'Editor',
        };
    }
}
