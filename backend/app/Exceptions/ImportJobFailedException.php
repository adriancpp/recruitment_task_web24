<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class ImportJobFailedException extends RuntimeException
{
    public function __construct(
        public readonly Throwable $causedBy
    ) {
        parent::__construct($causedBy->getMessage(), 0, $causedBy);
    }
}
