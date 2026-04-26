<?php

namespace App\Support;

final class ImportFileLimits
{
    /**
     * Maksymalny rozmiar pliku importu (kilobajty — zgodnie z regułą `file|max` w Laravel).
     */
    public const MAX_KILOBYTES = 51200;
}
