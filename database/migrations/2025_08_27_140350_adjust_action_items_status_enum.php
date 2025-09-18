<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Izinkan semua nilai status yang dipakai aplikasi
        DB::statement("
            ALTER TABLE action_items
            MODIFY COLUMN status ENUM(
                'Open',
                'Progress',
                'Request Close',
                'Closed',
                'Pending',
                'Cancel'
            ) NOT NULL DEFAULT 'Open'
        ");
    }

    public function down(): void
    {
        // (opsional) balik ke enum tanpa 'Request Close'
        DB::statement("
            ALTER TABLE action_items
            MODIFY COLUMN status ENUM(
                'Open',
                'Progress',
                'Closed',
                'Pending',
                'Cancel'
            ) NOT NULL DEFAULT 'Open'
        ");
    }
};
