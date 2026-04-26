<?php

namespace App\Services\Import\Parsers;

use Illuminate\Http\UploadedFile;

interface TransactionFileParser
{
    /**
     * @return array<int, array{
     *     transaction_id: mixed,
     *     account_number: mixed,
     *     transaction_date: mixed,
     *     amount: mixed,
     *     currency: mixed
     * }>
     */
    public function parse(UploadedFile $file): array;
}
