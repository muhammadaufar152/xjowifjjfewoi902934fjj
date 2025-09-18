<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('review_steps', function (Blueprint $t) {
            if (!Schema::hasColumn('review_steps', 'action_item_id')) {
                $t->unsignedBigInteger('action_item_id')->nullable()->after('form_review_id');
                $t->index('action_item_id', 'review_steps_action_item_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('review_steps', function (Blueprint $t) {
            if (Schema::hasColumn('review_steps', 'action_item_id')) {
                $t->dropIndex('review_steps_action_item_id_idx');
                $t->dropColumn('action_item_id');
            }
        });
    }
};
