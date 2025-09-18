<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sirkulir_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_review_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('uploaded_role', 20)->nullable(); // officer/manager/avp
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index('form_review_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sirkulir_files');
    }
};
