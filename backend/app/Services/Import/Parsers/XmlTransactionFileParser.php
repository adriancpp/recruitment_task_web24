<?php

namespace App\Services\Import\Parsers;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use SimpleXMLElement;

final class XmlTransactionFileParser implements TransactionFileParser
{
    public function parse(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath() ?: '');

        if ($contents === false) {
            throw new RuntimeException('Unable to read uploaded XML file.');
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        if ($xml === false) {
            throw new RuntimeException('Malformed XML file.');
        }

        if (!isset($xml->transaction)) {
            return [];
        }

        $records = [];

        foreach ($xml->transaction as $transactionNode) {
            $records[] = ParsedTransactionNormalizer::normalize((array) $transactionNode);
        }

        return $records;
    }
}
