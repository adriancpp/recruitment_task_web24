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

    public function test_partial_csv_from_repo_samples_is_marked_partial_with_logs(): void
    {
        $path = $this->samplePath('partial.csv');
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertNotFalse($content);

        $file = UploadedFile::fake()->createWithContent('partial.csv', $content);
        $response = $this->postJson('/api/imports', ['file' => $file]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'partial');
        $response->assertJsonPath('data.total_records', 2);
        $response->assertJsonPath('data.successful_records', 1);
        $response->assertJsonPath('data.failed_records', 1);

        $id = (int) $response->json('data.id');
        $show = $this->getJson('/api/imports/'.$id);
        $show->assertOk();
        $this->assertGreaterThanOrEqual(1, $show->json('logs.meta.total'));
    }

    public function test_json_upload_from_samples_succeeds(): void
    {
        $path = $this->samplePath('valid.json');
        $this->assertFileExists($path);
        $file = UploadedFile::fake()->createWithContent('valid.json', (string) file_get_contents($path));

        $response = $this->postJson('/api/imports', ['file' => $file]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'success');
        $response->assertJsonPath('data.total_records', 2);
    }

    public function test_xml_upload_from_samples_succeeds(): void
    {
        $path = $this->samplePath('valid.xml');
        $this->assertFileExists($path);
        $file = UploadedFile::fake()->createWithContent('valid.xml', (string) file_get_contents($path));

        $response = $this->postJson('/api/imports', ['file' => $file]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'success');
        $response->assertJsonPath('data.total_records', 2);
    }

    public function test_show_returns_404_for_unknown_import(): void
    {
        $this->getJson('/api/imports/999999999')->assertNotFound();
    }

    public function test_index_accepts_per_page_query(): void
    {
        $this->getJson('/api/imports?per_page=5')->assertOk();
    }
}
