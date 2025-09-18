<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'status')) {
                $table->string('status', 50)
                      ->nullable()
                      ->default('Updated')   // sesuaikan default yg kamu pakai
                      ->after('jenis_document');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
