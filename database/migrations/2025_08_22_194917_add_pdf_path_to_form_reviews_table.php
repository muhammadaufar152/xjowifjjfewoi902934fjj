<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('form_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('form_reviews', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('lampiran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('form_reviews', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });
    }
};
