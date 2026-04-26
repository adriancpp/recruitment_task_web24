<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ścieżka do pliku w katalogu `samples/` w root repozytorium (obok `backend/`).
     */
    protected function samplePath(string $filename): string
    {
        return dirname(base_path()).DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.$filename;
    }
}
