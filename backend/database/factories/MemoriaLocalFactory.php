<?php

namespace Database\Factories;

use App\Models\MemoriaLocal;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemoriaLocalFactory extends Factory
{
    protected $model = MemoriaLocal::class;

    public function definition(): array
    {
        return [
            'nome' => 'Ginásio Municipal',
            'tipo' => 'ginasio',
            'endereco' => $this->faker->address(),
            'descricao' => 'Espaço histórico do esporte local.',
            'publicado' => true,
        ];
    }
}
