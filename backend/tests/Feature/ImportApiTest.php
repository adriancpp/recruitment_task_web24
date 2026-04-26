<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_invalid_file_extensions(): void
    {
        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $response = $this->postJson('/api/imports', ['file' => $file]);

        $response->assertStatus(422);
    }

    public function test_it_imports_csv_and_lists_imports(): void
    {
        $csv = <<<'CSV'
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,DE89370400440532013000,2025-10-14,150000,PLN
CSV;

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csv);

        $create = $this->postJson('/api/imports', ['file' => $file]);

        $create->assertCreated();
        $create->assertJsonPath('data.status', 'success');
        $create->assertJsonPath('data.total_records', 1);
        $create->assertJsonPath('data.successful_records', 1);
        $create->assertJsonPath('data.failed_records', 0);

        $importId = (int) $create->json('data.id');

        $this->assertDatabaseHas('imports', [
            'id' => $importId,
            'status' => 'success',
        ]);

        $index = $this->getJson('/api/imports');
        $index->assertOk();
        $index->assertJsonPath('data.0.id', $importId);

        $show = $this->getJson('/api/imports/'.$importId);
        $show->assertOk();
        $show->assertJsonPath('import.id', $importId);
        $show->assertJsonPath('logs.meta.total', 0);
    }

    public function test_malformed_json_file_marks_import_as_failed_without_http_500(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.json', '{ not valid json');

        $response = $this->postJson('/api/imports', ['file' => $file]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'failed');
        $this->assertDatabaseHas('import_logs', [
            'import_id' => $response->json('data.id'),
        ]);
    }
}
