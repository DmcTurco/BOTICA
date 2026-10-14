<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Siembra los tipos de documento de identidad según estándar SUNAT (DocumentType::BASE).
     */
    public function run(): void
    {
        // Idempotente: solo crea los que falten
        DocumentType::crearTiposBase();
    }
}
