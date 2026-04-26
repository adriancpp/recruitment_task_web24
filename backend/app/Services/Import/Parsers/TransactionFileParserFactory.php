<?php

namespace App\Services\Import\Parsers;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class TransactionFileParserFactory
{
    public static function fromUploadedFile(UploadedFile $file): TransactionFileParser
    {
        return self::fromExtension($file->getClientOriginalExtension());
    }

    public static function fromExtension(string $extension): TransactionFileParser
    {
        return match (strtolower($extension)) {
            'csv' => new CsvTransactionFileParser(),
            'json' => new JsonTransactionFileParser(),
            'xml' => new XmlTransactionFileParser(),
            default => throw new InvalidArgumentException('Unsupported file type: '.$extension),
        };
    }
}
