<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('documents', 'created_by')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->foreignId('created_by')
                      ->nullable()
                      ->after('additional_file')
                      ->constrained('users')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documents', 'created_by')) {
            Schema::table('documents', function (Blueprint $table) {
                // Hapus FK + kolomnya (sesuai kebutuhanmu)
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
