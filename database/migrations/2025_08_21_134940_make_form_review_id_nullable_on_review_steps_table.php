<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Lepas FK bila ada
        Schema::table('review_steps', function (Blueprint $t) {
            try { $t->dropForeign(['form_review_id']); } catch (\Throwable $e) {}
        });

        // 2) Ubah kolom jadi NULLABLE (tanpa doctrine/dbal)
        DB::statement('ALTER TABLE review_steps MODIFY form_review_id BIGINT UNSIGNED NULL');

        // 3) Pasang lagi FK (opsional) dengan on delete set null
        Schema::table('review_steps', function (Blueprint $t) {
            $t->foreign('form_review_id')
              ->references('id')
              ->on('form_reviews')
              ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Balikkan perubahan (NOT NULL)
        Schema::table('review_steps', function (Blueprint $t) {
            try { $t->dropForeign(['form_review_id']); } catch (\Throwable $e) {}
        });

        DB::statement('ALTER TABLE review_steps MODIFY form_review_id BIGINT UNSIGNED NOT NULL');

        Schema::table('review_steps', function (Blueprint $t) {
            $t->foreign('form_review_id')
              ->references('id')
              ->on('form_reviews')
              ->cascadeOnDelete();
        });
    }
};
