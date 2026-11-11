<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a phone number for a country. India: 10-digit mobile starting 6-9
 * (an optional +91 / 0 prefix is tolerated). Other countries: 6-15 digits.
 */
class PhoneNumber implements ValidationRule
{
    public function __construct(private string $iso2 = 'IN') {}

    public static function normalize(?string $value, string $iso2 = 'IN'): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $value);
        if ($iso2 === 'IN') {
            $digits = preg_replace('/^(91|0)(?=[6-9]\d{9}$)/', '', $digits);
        }

        return $digits;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = static::normalize((string) $value, $this->iso2);
        $ok = $this->iso2 === 'IN'
            ? (bool) preg_match('/^[6-9]\d{9}$/', (string) $digits)
            : (bool) preg_match('/^\d{6,15}$/', (string) $digits);

        if (! $ok) {
            $fail($this->iso2 === 'IN' ? 'Enter a valid 10-digit Indian mobile number.' : 'Enter a valid phone number.');
        }
    }
}
