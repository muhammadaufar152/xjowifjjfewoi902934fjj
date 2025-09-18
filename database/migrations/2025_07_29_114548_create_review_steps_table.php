<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_review_id')->constrained('form_reviews')->onDelete('cascade');
            $table->string('tahapan');
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->string('verifikator');
            $table->date('tanggal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_steps');
    }
};
