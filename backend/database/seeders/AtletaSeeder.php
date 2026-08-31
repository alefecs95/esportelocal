<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Atleta;

class AtletaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Atleta::factory()->count(20)->create();
    }
}
