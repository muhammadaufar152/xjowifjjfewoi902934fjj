<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BusinessCycle;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        BusinessCycle::create(['name' => 'Inventory']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        BusinessCycle::where('name', 'Inventory')->delete();
    }
};