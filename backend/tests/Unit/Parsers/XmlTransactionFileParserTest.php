<?php

namespace Tests\Unit\Parsers;

use App\Services\Import\Parsers\XmlTransactionFileParser;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class XmlTransactionFileParserTest extends TestCase
{
    public function test_it_parses_valid_xml(): void
    {
        $xml = <<<'XML'
<transactions>
  <transaction>
    <transaction_id>550e8400-e29b-41d4-a716-446655440000</transaction_id>
    <account_number>DE89370400440532013000</account_number>
    <transaction_date>2025-10-14</transaction_date>
    <amount>150000</amount>
    <currency>PLN</currency>
  </transaction>
</transactions>
XML;

        $file = UploadedFile::fake()->createWithContent('t.xml', $xml);
        $parser = new XmlTransactionFileParser;

        $rows = $parser->parse($file);

        $this->assertCount(1, $rows);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $rows[0]['transaction_id']);
    }

    public function test_it_returns_empty_array_when_no_transaction_nodes(): void
    {
        $xml = '<transactions></transactions>';
        $file = UploadedFile::fake()->createWithContent('t.xml', $xml);
        $parser = new XmlTransactionFileParser;

        $this->assertSame([], $parser->parse($file));
    }

    public function test_it_throws_on_malformed_xml(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.xml', '<transactions><transaction>');
        $parser = new XmlTransactionFileParser;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Malformed XML');

        $parser->parse($file);
    }
}
