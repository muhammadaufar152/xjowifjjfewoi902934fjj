<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessProcess;

class BusinessProcessSeeder extends Seeder
{
    public function run(): void
    {
        $processes = [
            'Fufillment',
            'Assurance',
            'Billing',
            'Financial Management',
            'Procurement',
            'Asset Management',
            'HCM',
            'Audit & Risk Management',
            'Strategic & Enterprise Management',
            'IT Management',
            'General Affair',
            'Enterprise Governance',
            'Performance Report',
        ];

        foreach ($processes as $process) {
            BusinessProcess::create(['name' => $process]);
        }
    }
}
