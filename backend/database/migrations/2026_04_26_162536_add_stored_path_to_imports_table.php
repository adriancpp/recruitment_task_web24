<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Pierwotnie dodawała kolumnę `stored_path` — usunięta, żeby tabela `imports`
 * miała wyłącznie kolumny ze specyfikacji zadania. Plik zostaje jako no-op,
 * żeby istniejące wpisy w tabeli `migrations` nadal miały odpowiadający plik.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
