<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_reviews', function (Blueprint $table) {
            $table->id();

            // Relasi ke user yang login saat input (BPO)
            $table->foreignId('bpo_id')->constrained('users')->onDelete('cascade');

            // Data dokumen
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_approval')->nullable();

            $table->string('jenis_permohonan')->nullable();
            $table->text('latar_belakang')->nullable();
            $table->text('usulan_revisi')->nullable();

            $table->string('nama_dokumen')->nullable();
            $table->string('no_dokumen')->nullable();
            $table->string('level_dokumen')->nullable();
            $table->string('klasifikasi_siklus')->nullable();
            $table->string('jenis_dokumen')->nullable();
            $table->string('dokumen_terkait')->nullable();

            $table->string('status')->nullable();
            $table->string('perihal')->nullable();
            $table->string('lampiran')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_reviews');
    }
};
