<?php

namespace Database\Seeders;

use App\Models\Prefeitura;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Prefeitura de Sorocaba
        $sorocaba = Prefeitura::create([
            'nome' => 'Prefeitura de Sorocaba',
            'slug' => 'sorocaba',
            'dominio' => 'esporte.sorocaba.sp.gov.br',
            'cor_primaria' => '#e11d48', // Rose 600
            'ativo' => true,
        ]);

        // Definir contexto para criar usuários
        App::instance('current_tenant', $sorocaba);

        User::create([
            'name' => 'Admin Sorocaba',
            'email' => 'admin@sorocaba.sp.gov.br',
            'cpf' => '11111111111',
            'password' => Hash::make('password'),
            'perfil' => 'ADMIN',
            'status' => 'ATIVO',
            'prefeitura_id' => $sorocaba->id,
        ]);

        // 2. Prefeitura de Votorantim
        $votorantim = Prefeitura::create([
            'nome' => 'Prefeitura de Votorantim',
            'slug' => 'votorantim',
            'dominio' => null,
            'cor_primaria' => '#2563eb', // Blue 600
            'ativo' => true,
        ]);

        App::instance('current_tenant', $votorantim);

        User::create([
            'name' => 'Admin Votorantim',
            'email' => 'admin@votorantim.sp.gov.br',
            'cpf' => '22222222222',
            'password' => Hash::make('password'),
            'perfil' => 'ADMIN',
            'status' => 'ATIVO',
            'prefeitura_id' => $votorantim->id,
        ]);

        // 3. Super Admin (Global? Ou na prefeitura padrão?)
        // Vamos criar na prefeitura padrão (ID 1 geralmente, ou criada na migration)
        // Se a migration já rodou, deve existir o ID 1.
        $padrao = Prefeitura::find(1);
        if ($padrao) {
            App::instance('current_tenant', $padrao);
            
            User::create([
                'name' => 'Super Admin',
                'email' => 'super@semel.com',
                'cpf' => '00000000000',
                'password' => Hash::make('password'),
                'perfil' => 'SUPER_ADMIN',
                'status' => 'ATIVO',
                'prefeitura_id' => $padrao->id,
            ]);
        }
    }
}
