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
        Schema::create('bpo_uploaded_files', function (Blueprint $table) {
            $table->id();

            // Relasi ke form_reviews (hapus otomatis kalau form review dihapus)
            $table->foreignId('form_review_id')
                  ->constrained('form_reviews')
                  ->onDelete('cascade');

            // Path file di storage/public
            $table->string('file_path', 1024);

            // Keterangan opsional per file
            $table->string('keterangan')->nullable();

            // Siapa yang meng-upload (optional, null jika tidak ada)
            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // (opsional) index tambahan
            // $table->index(['form_review_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bpo_uploaded_files');
    }
};
