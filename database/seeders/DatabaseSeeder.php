<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // foreach ($users as $u) {
        //     User::create([
        //         'name' => $u['name'] ?? 'no name',
        //         'username' => $u['username'],
        //         'email' => $u['email'],
        //         'role' => 'bpo',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('Telkomsat'),
        //         'remember_token' => Str::random(10),
        //     ]);
        // }

        // Admin
        User::create([
            'name' => 'Admin Telkomsat',
            'username' => 'admin',
            'email' => 'admin@telkomsat.id',
            'role' => 'admin',
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // ganti kalau perlu
            'remember_token' => Str::random(10),
        ]);

        // BPO
        User::create([
            'name' => 'BPO User',
            'username' => 'bpo',
            'email' => 'bpo@telkomsat.id',
            'role' => 'bpo',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);

        // Officer Bispro
        User::create([
            'name' => 'Officer Bispro',
            'username' => 'officer',
            'email' => 'officer@telkomsat.id',
            'role' => 'officer',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);

        // Manager Bispro
        User::create([
            'name' => 'Manager Bispro',
            'username' => 'manager',
            'email' => 'manager@telkomsat.id',
            'role' => 'manager',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);

        // AVP Bispro
        User::create([
            'name' => 'AVP Bispro',
            'username' => 'avp',
            'email' => 'avp@telkomsat.id',
            'role' => 'avp',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);

        $this->call([
            DocumentTypeSeeder::class,
            BusinessProcessSeeder::class,
            BusinessCycleSeeder::class,
        ]);
    }
}
