<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportLogResource;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $imports = Import::query()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return ImportResource::collection($imports);
    }

    public function store(StoreImportRequest $request): JsonResponse
    {
        $file = $request->file('file');
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
            // Synchronicznie: od razu finalny `status` w odpowiedzi. Na produkcji z workerem zamień na `ProcessImportJob::dispatch(...)`.
            Bus::dispatchSync(new ProcessImportJob($import->id));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Import processing failed.',
            ], 500);
        }

        return (new ImportResource($import->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Import $import): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 50), 1), 200);

        $logs = $import->importLogs()
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
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
        ]);
    }
}
