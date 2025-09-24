<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessCycle;

class BusinessCycleSeeder extends Seeder
{
    public function run(): void
    {
        $cycles = [
            'Revenue',
            'Cost',
            'Tax',
            'Procurement & Asset Management',
            'Financial Reporting',
            'Treasury',
            'Planning & System Management',
            'General Affair',
            'IT Management',
            'Inventory',
        ];

        foreach ($cycles as $cycle) {
            BusinessCycle::create(['name' => $cycle]);
        }
    }
}

