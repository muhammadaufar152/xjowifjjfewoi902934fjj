<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            if (!Schema::hasColumn('action_items', 'nama_dokumen')) {
                $table->string('nama_dokumen')->nullable()->after('id');
            }
            if (!Schema::hasColumn('action_items', 'no_dokumen')) {
                $table->string('no_dokumen')->nullable()->after('nama_dokumen');
            }
            if (!Schema::hasColumn('action_items', 'action_item')) {
                $table->string('action_item')->nullable()->after('no_dokumen');
            }
            if (!Schema::hasColumn('action_items', 'bpo_uic')) {
                $table->string('bpo_uic')->nullable()->after('action_item');
            }
            if (!Schema::hasColumn('action_items', 'target')) {
                $table->date('target')->nullable()->after('bpo_uic');
            }
            if (!Schema::hasColumn('action_items', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('target');
            }
            if (!Schema::hasColumn('action_items', 'no_fr')) {
                $table->string('no_fr')->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('action_items', 'lampiran')) {
                $table->string('lampiran')->nullable()->after('no_fr');
            }
        });

        // (opsional) Backfill dari kolom versi kamu supaya data lama ikut terisi
        if (Schema::hasColumn('action_items', 'title')) {
            DB::statement('UPDATE action_items SET nama_dokumen = COALESCE(nama_dokumen, title)');
        }
        if (Schema::hasColumn('action_items', 'description')) {
            DB::statement('UPDATE action_items SET keterangan = COALESCE(keterangan, description)');
        }
        if (Schema::hasColumn('action_items', 'due_date')) {
            DB::statement('UPDATE action_items SET target = COALESCE(target, due_date)');
        }
        if (Schema::hasColumn('action_items', 'lampiran_path')) {
            DB::statement('UPDATE action_items SET lampiran = COALESCE(lampiran, lampiran_path)');
        }
    }

    public function down(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            if (Schema::hasColumn('action_items', 'lampiran'))    $table->dropColumn('lampiran');
            if (Schema::hasColumn('action_items', 'no_fr'))       $table->dropColumn('no_fr');
            if (Schema::hasColumn('action_items', 'keterangan'))  $table->dropColumn('keterangan');
            if (Schema::hasColumn('action_items', 'target'))      $table->dropColumn('target');
            if (Schema::hasColumn('action_items', 'bpo_uic'))     $table->dropColumn('bpo_uic');
            if (Schema::hasColumn('action_items', 'action_item')) $table->dropColumn('action_item');
            if (Schema::hasColumn('action_items', 'no_dokumen'))  $table->dropColumn('no_dokumen');
            if (Schema::hasColumn('action_items', 'nama_dokumen'))$table->dropColumn('nama_dokumen');
        });
    }
};
