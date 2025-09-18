<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('nama_document');
            $table->string('nomor_document');
            $table->date('tanggal_terbit');
            $table->string('siklus_bisnis');
            $table->string('proses_bisnis');
            $table->string('business_process_owner');
            $table->string('jenis_document');
            $table->string('status')->nullable();
            $table->string('version')->nullable();
            $table->string('additional_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('documents');
    }
};
