<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Cheap bot protection that works on shared hosting without third-party services:
 * a hidden honeypot field and a minimum time between form render and submit.
 */
class SpamGuard
{
    public const HONEYPOT = 'website';

    public const TIMER = 'form_token';

    public const MIN_SECONDS = 3;

    public static function token(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    public static function looksLikeBot(Request $request): bool
    {
        if (filled($request->input(self::HONEYPOT))) {
            return true;
        }
        try {
            $started = (int) Crypt::decryptString((string) $request->input(self::TIMER));
        } catch (\Throwable) {
            return true;
        }

        return now()->timestamp - $started < self::MIN_SECONDS;
    }
}
