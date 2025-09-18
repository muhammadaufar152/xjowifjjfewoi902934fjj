<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Kalau tabel yang benar (plural) sudah ada, lewati
        if (Schema::hasTable('action_items')) {
            return;
        }

        // 2) Kalau yang ada masih singular, rename ke plural
        if (Schema::hasTable('action_item')) {
            Schema::rename('action_item', 'action_items');
            return;
        }

        // 3) Kalau dua-duanya belum ada, buat tabel baru (plural)
        Schema::create('action_items', function (Blueprint $t) {
            $t->id();
            $t->string('nama_dokumen');      // Nama Dokumen
            $t->string('no_dokumen');        // No Dokumen
            $t->text('action_item');         // Action Item
            $t->string('bpo_uic');           // BPO / UIC (bisa diubah ke FK users nanti)
            $t->date('target')->nullable();  // Target
            $t->enum('status', ['Open','Progress','Pending','Request Close','Closed','Cancel'])
              ->default('Open');             // Status
            $t->text('keterangan')->nullable(); // Keterangan
            $t->string('lampiran')->nullable(); // Lampiran (path file)
            $t->string('no_fr')->nullable();    // No FR
            $t->timestamps();

            $t->index('status');
            $t->index('target');
            $t->index('no_dokumen');
            $t->index('no_fr');
        });
    }

    public function down(): void
    {
        // Turunkan tabel plural (yang dipakai app)
        Schema::dropIfExists('action_items');

        // (opsional) kalau ada sisa singular, hapus juga agar bersih
        Schema::dropIfExists('action_item');
    }
};
