<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Drop the old column from documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('business_process_owner_id');
        });

        // Create the pivot table
        Schema::create('document_bpo', function (Blueprint $table) {
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete()
                ->name('document_id_foreign');
            $table->foreignId('business_process_owner_id')
                ->constrained('business_process_owners')
                ->cascadeOnDelete()
                ->name('bpo_id_foreign');
            $table->primary(['document_id', 'business_process_owner_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('document_bpo');
        Schema::table('documents', function (Blueprint $table) {
            $table->integer('business_process_owner_id')->nullable();
        });
    }
};
