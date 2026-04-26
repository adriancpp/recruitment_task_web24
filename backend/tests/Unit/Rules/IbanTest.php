<?php

namespace Tests\Unit\Rules;

use App\Rules\Iban;
use PHPUnit\Framework\TestCase;

class IbanTest extends TestCase
{
    public function test_it_accepts_known_valid_iban(): void
    {
        $this->assertTrue(Iban::isValid('DE89370400440532013000'));
    }

    public function test_it_rejects_invalid_checksum(): void
    {
        $this->assertFalse(Iban::isValid('DE89370400440532013001'));
    }

    public function test_it_rejects_garbage(): void
    {
        $this->assertFalse(Iban::isValid('NOT-AN-IBAN'));
    }

    public function test_it_accepts_iban_with_spaces(): void
    {
        $this->assertTrue(Iban::isValid('DE89 3704 0044 0532 0130 00'));
    }
}
