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
            $table->foreignId('business_cycle_id')
              ->constrained('business_cycles')
              ->cascadeOnDelete();

            $table->foreignId('business_process_id')
              ->constrained('business_processes')
              ->cascadeOnDelete();

            $table->integer('business_process_owner_id');
            $table->foreignId('document_type_id')
              ->constrained('document_types')
              ->cascadeOnDelete();
              
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
