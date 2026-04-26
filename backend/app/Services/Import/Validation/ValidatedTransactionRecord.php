<?php

namespace App\Services\Import\Validation;

final class ValidatedTransactionRecord
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $errors
     */
    public function __construct(
        public readonly array $data,
        public readonly array $errors
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function errorMessage(): string
    {
        return implode('; ', $this->errors);
    }
}
