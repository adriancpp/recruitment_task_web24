<?php

namespace App\Services\Import;

use App\Exceptions\ImportJobFailedException;
use App\Http\Resources\ImportLogResource;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportApiService
{
    public function paginateImports(Request $request): LengthAwarePaginator
    {
        $perPage = $this->clampInt((int) $request->query('per_page', 15), 1, 100);

        return Import::query()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Tworzy rekord importu, zapisuje plik i uruchamia przetwarzanie (sync).
     *
     * @throws ImportJobFailedException gdy job importu rzuci wyjątek
     */
    public function storeFromUploadedFile(UploadedFile $file): Import
    {
        $fileName = basename((string) $file->getClientOriginalName());

        $import = DB::transaction(function () use ($file, $fileName): Import {
            $import = Import::query()->create([
                'file_name' => $fileName,
                'total_records' => 0,
                'successful_records' => 0,
                'failed_records' => 0,
                'status' => 'failed',
                'created_at' => now(),
            ]);

            $file->storeAs('imports/'.$import->id, $fileName, 'local');

            return $import;
        });

        try {
            // Synchronicznie: od razu finalny `status` w odpowiedzi. Na produkcji z workerem: `ProcessImportJob::dispatch(...)`.
            Bus::dispatchSync(new ProcessImportJob($import->id));
        } catch (Throwable $exception) {
            Log::error('import.job_failed', [
                'import_id' => $import->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            throw new ImportJobFailedException($exception);
        }

        return $import->fresh();
    }

    /**
     * @return array{import: array<string, mixed>, logs: array<string, mixed>}
     */
    public function buildImportDetailPayload(Request $request, Import $import): array
    {
        $perPage = $this->clampInt((int) $request->query('per_page', 50), 1, 200);

        $logs = $import->importLogs()
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'import' => (new ImportResource($import))->toArray($request),
            'logs' => [
                'data' => ImportLogResource::collection($logs->getCollection())->resolve(),
                'links' => [
                    'first' => $logs->url(1),
                    'last' => $logs->url($logs->lastPage()),
                    'prev' => $logs->previousPageUrl(),
                    'next' => $logs->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'from' => $logs->firstItem(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'to' => $logs->lastItem(),
                    'total' => $logs->total(),
                ],
            ],
        ];
    }

    private function clampInt(int $value, int $min, int $max): int
    {
        return min(max($value, $min), $max);
    }
}
