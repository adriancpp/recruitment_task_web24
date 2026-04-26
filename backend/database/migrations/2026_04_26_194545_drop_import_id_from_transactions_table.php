<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'import_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['import_id']);
            $table->dropColumn('import_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'import_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('import_id')->after('id')->constrained('imports')->cascadeOnDelete();
        });
    }
};
