<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_item_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // lokasi file di storage
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampirans');
    }
};
