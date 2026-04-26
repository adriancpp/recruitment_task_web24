<?php

namespace Tests\Unit\Validation;

use App\Services\Import\Validation\TransactionRecordValidator;
use Tests\TestCase;

class TransactionRecordValidatorTest extends TestCase
{
    private TransactionRecordValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TransactionRecordValidator;
    }

    public function test_it_accepts_valid_record(): void
    {
        $result = $this->validator->validate([
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'DE89370400440532013000',
            'transaction_date' => '2025-10-14',
            'amount' => 150000,
            'currency' => 'PLN',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame('PLN', $result->data['currency']);
    }

    public function test_it_rejects_invalid_iban(): void
    {
        $result = $this->validator->validate([
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'XX00',
            'transaction_date' => '2025-10-14',
            'amount' => 100,
            'currency' => 'PLN',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->errors);
    }

    public function test_it_rejects_non_positive_amount(): void
    {
        $result = $this->validator->validate([
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'DE89370400440532013000',
            'transaction_date' => '2025-10-14',
            'amount' => 0,
            'currency' => 'PLN',
        ]);

        $this->assertFalse($result->isValid());
    }

    public function test_it_rejects_invalid_currency_format(): void
    {
        $result = $this->validator->validate([
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'DE89370400440532013000',
            'transaction_date' => '2025-10-14',
            'amount' => 100,
            'currency' => 'pln',
        ]);

        $this->assertFalse($result->isValid());
    }

    public function test_it_rejects_invalid_uuid(): void
    {
        $result = $this->validator->validate([
            'transaction_id' => 'not-a-uuid',
            'account_number' => 'DE89370400440532013000',
            'transaction_date' => '2025-10-14',
            'amount' => 100,
            'currency' => 'PLN',
        ]);

        $this->assertFalse($result->isValid());
    }
}
