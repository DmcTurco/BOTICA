<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Cliente predeterminado del punto de venta: "CLIENTE PÚBLICO" con DNI 00000000.
     * Se crea para la compañía 1; las demás lo reciben al abrir el POS (Client::publicFor).
     */
    public function run(): void
    {
        Client::publicFor(1);
    }
}
