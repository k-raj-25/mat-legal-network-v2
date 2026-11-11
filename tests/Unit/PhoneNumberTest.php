<?php

namespace Tests\Unit;

use App\Rules\PhoneNumber;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalizes_indian_numbers(): void
    {
        $this->assertSame('9876543210', PhoneNumber::normalize('+91 98765-43210'));
        $this->assertSame('9876543210', PhoneNumber::normalize('09876543210'));
        $this->assertSame('9876543210', PhoneNumber::normalize('919876543210'));
        $this->assertNull(PhoneNumber::normalize('  '));
    }

    public function test_validates(): void
    {
        foreach (['9876543210', '+91 6000000000', '07000000000'] as $ok) {
            $this->assertTrue(Validator::make(['p' => $ok], ['p' => new PhoneNumber])->passes(), $ok);
        }
        foreach (['12345', '5876543210', 'abcdefghij', '98765432101'] as $bad) {
            $this->assertFalse(Validator::make(['p' => $bad], ['p' => new PhoneNumber])->passes(), $bad);
        }
        $this->assertTrue(Validator::make(['p' => '+44 20 7946 0958'], ['p' => new PhoneNumber('GB')])->passes());
    }
}
