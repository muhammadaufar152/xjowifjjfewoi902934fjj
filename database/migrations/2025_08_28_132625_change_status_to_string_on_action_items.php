<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            $table->string('status', 50)->default('Open')->change();
        });

        // (Opsional) CHECK constraint – MySQL 8.0.16+
        DB::statement("
            ALTER TABLE action_items
            ADD CONSTRAINT chk_action_items_status
            CHECK (status IN ('Open','Progress','Pending','Request Close','Closed','Cancelled'))
        ");
    }

    public function down(): void
    {
        // Lepas CHECK (abaikan jika versi MySQL tidak mendukung)
        try { DB::statement("ALTER TABLE action_items DROP CHECK chk_action_items_status"); } catch (\Throwable $e) {}

        // Kembali ke ENUM jika perlu
        DB::statement("
            ALTER TABLE action_items
            MODIFY status ENUM('Open','Progress','Pending','Request Close','Closed')
            NOT NULL DEFAULT 'Open'
        ");
    }
};
