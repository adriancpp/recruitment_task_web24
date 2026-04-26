<?php

namespace App\Services\Import;

use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use App\Services\Import\Parsers\TransactionFileParserFactory;
use App\Services\Import\Validation\TransactionRecordValidator;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class ImportService
{
    public function __construct(
        private readonly TransactionRecordValidator $validator
    ) {
    }

    public function process(Import $import): void
    {
        $relativePath = $import->storedFileRelativePath();

        if (! Storage::disk('local')->exists($relativePath)) {
            $this->failImport($import, 'Uploaded file is no longer available.');

            return;
        }

        $absolutePath = Storage::disk('local')->path($relativePath);

        try {
            $extension = strtolower(pathinfo($import->file_name, PATHINFO_EXTENSION));
            $parser = TransactionFileParserFactory::fromExtension($extension);

            $mimeType = @mime_content_type($absolutePath) ?: 'application/octet-stream';
            $uploadedFile = new UploadedFile(
                $absolutePath,
                $import->file_name,
                $mimeType,
                null,
                true
            );

            $records = $parser->parse($uploadedFile);
        } catch (Throwable $exception) {
            Log::warning('import.parse_failed', [
                'import_id' => $import->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            $this->failImport($import, 'File could not be processed: '.$exception->getMessage());

            return;
        }

        $totalRecords = count($records);

        DB::transaction(function () use ($import, $records, $totalRecords): void {
            $successful = 0;
            $failed = 0;

            foreach ($records as $rowIndex => $record) {
                try {
                    $validated = $this->validator->validate($record);

                    if ($validated->isValid()) {
                        try {
                            Transaction::query()->create([
                                'transaction_id' => $validated->data['transaction_id'],
                                'account_number' => $validated->data['account_number'],
                                'transaction_date' => Carbon::parse($validated->data['transaction_date'])->toDateString(),
                                'amount' => (string) $validated->data['amount'],
                                'currency' => $validated->data['currency'],
                            ]);

                            $successful++;
                        } catch (QueryException $exception) {
                            Log::warning('import.transaction_insert_failed', [
                                'import_id' => $import->id,
                                'row_index' => $rowIndex,
                                'exception' => $exception::class,
                                'message' => $exception->getMessage(),
                            ]);

                            report($exception);

                            ImportLog::query()->create([
                                'import_id' => $import->id,
                                'transaction_id' => $this->transactionIdForLog($record),
                                'error_message' => 'Could not save this transaction (database error).',
                            ]);

                            $failed++;
                        }
                    } else {
                        ImportLog::query()->create([
                            'import_id' => $import->id,
                            'transaction_id' => $this->transactionIdForLog($record),
                            'error_message' => $validated->errorMessage(),
                        ]);

                        $failed++;
                    }
                } catch (Throwable $exception) {
                    Log::error('import.row_processing_failed', [
                        'import_id' => $import->id,
                        'row_index' => $rowIndex,
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ]);

                    report($exception);

                    ImportLog::query()->create([
                        'import_id' => $import->id,
                        'transaction_id' => $this->transactionIdForLog($record),
                        'error_message' => 'Unexpected error while processing this row.',
                    ]);

                    $failed++;
                }
            }

            $import->update([
                'total_records' => $totalRecords,
                'successful_records' => $successful,
                'failed_records' => $failed,
                'status' => $this->determineStatus($successful, $failed, $totalRecords),
            ]);
        });

        Storage::disk('local')->delete($relativePath);
    }

    private function failImport(Import $import, string $message): void
    {
        Log::warning('import.failed', [
            'import_id' => $import->id,
            'message' => $message,
        ]);

        DB::transaction(function () use ($import, $message): void {
            ImportLog::query()->create([
                'import_id' => $import->id,
                'transaction_id' => null,
                'error_message' => $message,
            ]);

            $import->update([
                'total_records' => 0,
                'successful_records' => 0,
                'failed_records' => 0,
                'status' => 'failed',
            ]);
        });

        Storage::disk('local')->delete($import->storedFileRelativePath());
    }

    /**
     * @param array<string, mixed> $record
     */
    private function transactionIdForLog(array $record): ?string
    {
        $id = $record['transaction_id'] ?? null;

        if (! is_string($id)) {
            return null;
        }

        $id = trim($id);

        return Str::isUuid($id) ? $id : null;
    }

    private function determineStatus(int $successful, int $failed, int $totalRecords): string
    {
        if ($totalRecords === 0) {
            return 'failed';
        }

        if ($successful === $totalRecords) {
            return 'success';
        }

        if ($successful > 0 && $failed > 0) {
            return 'partial';
        }

        if ($successful === 0 && $failed > 0) {
            return 'failed';
        }

        return 'failed';
    }
}
