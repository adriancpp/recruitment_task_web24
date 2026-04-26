<?php

namespace App\Services\Import\Parsers;

use Illuminate\Http\UploadedFile;
use JsonException;
use RuntimeException;

final class JsonTransactionFileParser implements TransactionFileParser
{
    public function parse(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath() ?: '');

        if ($contents === false) {
            throw new RuntimeException('Unable to read uploaded JSON file.');
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Malformed JSON file: '.$exception->getMessage(), 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('JSON root must be an object or an array.');
        }

        $rows = array_is_list($decoded) ? $decoded : [$decoded];
        $records = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new RuntimeException('Each JSON transaction must be an object.');
            }

            $records[] = ParsedTransactionNormalizer::normalize($row);
        }

        return $records;
    }
}
