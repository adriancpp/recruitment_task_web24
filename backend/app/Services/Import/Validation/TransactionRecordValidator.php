<?php

namespace App\Services\Import\Validation;

use Illuminate\Support\Facades\Validator;

final class TransactionRecordValidator
{
    /**
     * @param array<string, mixed> $record
     */
    public function validate(array $record): ValidatedTransactionRecord
    {
        $validator = Validator::make(
            $record,
            [
                'transaction_id' => ['required', 'uuid'],
                'account_number' => ['required', 'iban'],
                'transaction_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'gt:0'],
                'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            ],
            [
                'transaction_id.required' => 'Missing transaction_id.',
                'transaction_id.uuid' => 'transaction_id must be a valid UUID.',
                'account_number.required' => 'Missing account_number.',
                'account_number.iban' => 'account_number must be a valid IBAN.',
                'transaction_date.required' => 'Missing transaction_date.',
                'transaction_date.date' => 'transaction_date must be a valid date.',
                'amount.required' => 'Missing amount.',
                'amount.numeric' => 'amount must be numeric.',
                'amount.gt' => 'amount must be greater than 0.',
                'currency.required' => 'Missing currency.',
                'currency.regex' => 'currency must be a 3-letter uppercase code.',
            ]
        );

        return new ValidatedTransactionRecord(
            data: $this->normalizeData($record),
            errors: $validator->errors()->all()
        );
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function normalizeData(array $record): array
    {
        if (isset($record['currency']) && is_string($record['currency'])) {
            $record['currency'] = strtoupper(trim($record['currency']));
        }

        return $record;
    }
}
