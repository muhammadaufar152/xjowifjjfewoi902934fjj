<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Bispro', 'Prosedur', 'Instruksi Kerja', 'Form'];
        foreach ($types as $type) {
            DocumentType::create(['name' => $type]);
        }
    }
}
