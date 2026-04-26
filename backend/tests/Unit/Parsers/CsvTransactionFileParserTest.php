<?php

namespace Tests\Unit\Parsers;

use App\Services\Import\Parsers\CsvTransactionFileParser;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class CsvTransactionFileParserTest extends TestCase
{
    public function test_it_parses_valid_csv_rows(): void
    {
        $csv = <<<'CSV'
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,DE89370400440532013000,2025-10-14,150000,PLN
550e8400-e29b-41d4-a716-446655440001,DE89370400440532013000,2025-10-13,20050,USD
CSV;

        $file = UploadedFile::fake()->createWithContent('t.csv', $csv);
        $parser = new CsvTransactionFileParser;
        $rows = $parser->parse($file);

        $this->assertCount(2, $rows);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $rows[0]['transaction_id']);
        $this->assertSame('DE89370400440532013000', $rows[0]['account_number']);
        $this->assertSame('2025-10-14', $rows[0]['transaction_date']);
        $this->assertSame('150000', $rows[0]['amount']);
        $this->assertSame('PLN', $rows[0]['currency']);
    }

    public function test_it_throws_on_empty_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent('empty.csv', '');
        $parser = new CsvTransactionFileParser;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('empty');

        $parser->parse($file);
    }
}
