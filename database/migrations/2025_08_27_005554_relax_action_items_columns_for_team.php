<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            // Kolom "baru" dari skema kamu yang bikin tabrakan dengan controller lama
            if (Schema::hasColumn('action_items', 'title')) {
                $table->string('title')->nullable()->change();
            }
            if (Schema::hasColumn('action_items', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            }
            if (Schema::hasColumn('action_items', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->change();
            }
            // Lampiran versi baru (kalau ada), biarkan nullable saja
            if (Schema::hasColumn('action_items', 'lampiran_path')) {
                $table->string('lampiran_path')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // optional: tidak usah diisi (safe)
    }
};
