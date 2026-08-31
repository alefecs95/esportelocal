<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@example.com');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'senha123')),
                'perfil' => 'SUPER_ADMIN',
                'cpf' => '11111111111',
                'status' => 'ATIVO',
                'forcar_troca_senha' => false,
                'prefeitura_id' => null,
            ]
        );

        $user->update([
            'perfil' => 'SUPER_ADMIN',
            'forcar_troca_senha' => false,
            'prefeitura_id' => null,
        ]);
    }
}
