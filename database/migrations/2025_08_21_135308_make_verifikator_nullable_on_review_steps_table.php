<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ubah kolom 'verifikator' menjadi NULLABLE (tanpa doctrine/dbal)
        // Sesuaikan tipe kolom jika di DB kamu berbeda (varchar(255) adalah yang umum)
        DB::statement("ALTER TABLE review_steps MODIFY verifikator VARCHAR(255) NULL");
    }

    public function down(): void
    {
        // Kembalikan menjadi NOT NULL jika diperlukan
        DB::statement("ALTER TABLE review_steps MODIFY verifikator VARCHAR(255) NOT NULL");
    }
};
