<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('action_items', function (Blueprint $table) {
            $table->boolean('is_upload_locked')->default(false)->after('status');
            $table->string('upload_lock_reason')->nullable()->after('is_upload_locked');
            $table->foreignId('upload_locked_by')->nullable()->after('upload_lock_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('upload_locked_at')->nullable()->after('upload_locked_by');
        });
    }
    public function down(): void {
        Schema::table('action_items', function (Blueprint $table) {
            $table->dropColumn(['is_upload_locked','upload_lock_reason','upload_locked_by','upload_locked_at']);
        });
    }
};
