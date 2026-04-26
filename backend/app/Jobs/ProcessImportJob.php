<?php

namespace App\Jobs;

use App\Models\Import;
use App\Services\Import\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(
        public readonly int $importId
    ) {
    }

    public function handle(ImportService $importService): void
    {
        $import = Import::query()->findOrFail($this->importId);

        $importService->process($import);
    }
}
