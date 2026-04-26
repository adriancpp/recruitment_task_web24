<?php

namespace Tests\Unit\Parsers;

use App\Services\Import\Parsers\ParsedTransactionNormalizer;
use PHPUnit\Framework\TestCase;

class ParsedTransactionNormalizerTest extends TestCase
{
    public function test_it_normalizes_mixed_case_keys(): void
    {
        $normalized = ParsedTransactionNormalizer::normalize([
            'Transaction_ID' => '550e8400-e29b-41d4-a716-446655440000',
            'ACCOUNT_NUMBER' => 'DE89370400440532013000',
        ]);

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $normalized['transaction_id']);
        $this->assertSame('DE89370400440532013000', $normalized['account_number']);
    }
}
