<?php

namespace App\Services\Import\Parsers;

final class ParsedTransactionNormalizer
{
    /**
     * @param array<string, mixed> $record
     * @return array{
     *     transaction_id: mixed,
     *     account_number: mixed,
     *     transaction_date: mixed,
     *     amount: mixed,
     *     currency: mixed
     * }
     */
    public static function normalize(array $record): array
    {
        $normalized = [];

        foreach ($record as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            $normalized[$normalizedKey] = $value;
        }

        return [
            'transaction_id' => $normalized['transaction_id'] ?? null,
            'account_number' => $normalized['account_number'] ?? null,
            'transaction_date' => $normalized['transaction_date'] ?? null,
            'amount' => $normalized['amount'] ?? null,
            'currency' => $normalized['currency'] ?? null,
        ];
    }
}
