<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ImportJobFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Models\Import;
use App\Services\Import\ImportApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ImportController extends Controller
{
    public function __construct(
        private readonly ImportApiService $importApi
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $imports = $this->importApi->paginateImports($request);

        return ImportResource::collection($imports);
    }

    public function store(StoreImportRequest $request): JsonResponse
    {
        try {
            $import = $this->importApi->storeFromUploadedFile($request->file('file'));
        } catch (ImportJobFailedException $exception) {
            return response()->json([
                'message' => app()->hasDebugModeEnabled()
                    ? $exception->causedBy->getMessage()
                    : 'Import processing failed.',
            ], 500);
        }

        return (new ImportResource($import))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Import $import): JsonResponse
    {
        return response()->json(
            $this->importApi->buildImportDetailPayload($request, $import)
        );
    }
}
