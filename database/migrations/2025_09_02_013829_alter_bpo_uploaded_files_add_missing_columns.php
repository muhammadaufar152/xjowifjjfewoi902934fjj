<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bpo_uploaded_files', function (Blueprint $table) {
            // relasi ke form_reviews
            if (!Schema::hasColumn('bpo_uploaded_files','form_review_id')) {
                $table->unsignedBigInteger('form_review_id')->after('id')->index();
                $table->foreign('form_review_id')
                      ->references('id')->on('form_reviews')
                      ->onDelete('cascade');
            }

            if (!Schema::hasColumn('bpo_uploaded_files','path')) {
                $table->string('path', 2048)->after('form_review_id');
            }
            if (!Schema::hasColumn('bpo_uploaded_files','original_name')) {
                $table->string('original_name')->nullable()->after('path');
            }
            if (!Schema::hasColumn('bpo_uploaded_files','keterangan')) {
                $table->string('keterangan')->nullable()->after('original_name');
            }
            if (!Schema::hasColumn('bpo_uploaded_files','uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->index()->after('keterangan');
                // kalau mau, bisa tambahkan FK ke users:
                // $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bpo_uploaded_files','uploaded_role')) {
                $table->string('uploaded_role', 50)->nullable()->after('uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bpo_uploaded_files', function (Blueprint $table) {
            // lepas foreign key bila ada
            if (Schema::hasColumn('bpo_uploaded_files','form_review_id')) {
                try { $table->dropForeign(['form_review_id']); } catch (\Throwable $e) {}
            }
            $cols = ['path','original_name','keterangan','uploaded_by','uploaded_role','form_review_id'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('bpo_uploaded_files',$c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
