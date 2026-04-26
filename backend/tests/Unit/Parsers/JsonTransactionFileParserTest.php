<?php

namespace Tests\Unit\Parsers;

use App\Services\Import\Parsers\JsonTransactionFileParser;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class JsonTransactionFileParserTest extends TestCase
{
    public function test_it_parses_json_array(): void
    {
        $json = '[{"transaction_id":"550e8400-e29b-41d4-a716-446655440000","account_number":"DE89370400440532013000","transaction_date":"2025-10-14","amount":1,"currency":"PLN"}]';
        $file = UploadedFile::fake()->createWithContent('t.json', $json);
        $parser = new JsonTransactionFileParser;

        $rows = $parser->parse($file);

        $this->assertCount(1, $rows);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $rows[0]['transaction_id']);
    }

    public function test_it_parses_single_json_object_as_one_row(): void
    {
        $json = '{"transaction_id":"550e8400-e29b-41d4-a716-446655440000","account_number":"DE89370400440532013000","transaction_date":"2025-10-14","amount":1,"currency":"PLN"}';
        $file = UploadedFile::fake()->createWithContent('t.json', $json);
        $parser = new JsonTransactionFileParser;

        $rows = $parser->parse($file);

        $this->assertCount(1, $rows);
    }

    public function test_it_throws_on_invalid_json(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.json', '{');
        $parser = new JsonTransactionFileParser;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Malformed JSON');

        $parser->parse($file);
    }

    public function test_it_throws_when_row_is_not_object(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.json', '[1,2,3]');
        $parser = new JsonTransactionFileParser;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('object');

        $parser->parse($file);
    }
}
