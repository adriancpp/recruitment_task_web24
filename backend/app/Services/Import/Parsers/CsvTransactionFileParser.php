<?php

namespace App\Services\Import\Parsers;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use SplFileObject;

final class CsvTransactionFileParser implements TransactionFileParser
{
    public function parse(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();

        if ($realPath === false) {
            throw new RuntimeException('Unable to access the uploaded CSV file.');
        }

        $csv = new SplFileObject($realPath);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $header = $csv->fgetcsv();

        if ($header === false || $header === [null]) {
            throw new RuntimeException('CSV file is empty or has no header.');
        }

        $header = array_map(static fn ($column) => strtolower(trim((string) $column)), $header);
        $records = [];

        while (!$csv->eof()) {
            $row = $csv->fgetcsv();

            if ($row === false || $row === [null]) {
                continue;
            }

            $row = array_pad($row, count($header), null);
            $record = array_combine($header, array_slice($row, 0, count($header)));

            if ($record === false) {
                throw new RuntimeException('Malformed CSV row encountered during parsing.');
            }

            $records[] = ParsedTransactionNormalizer::normalize($record);
        }

        return $records;
    }
}
