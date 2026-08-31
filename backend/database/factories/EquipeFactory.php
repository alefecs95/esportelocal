<?php

namespace Database\Factories;

use App\Models\Campeonato;
use App\Models\Prefeitura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipe>
 */
class EquipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prefeitura_id' => Prefeitura::factory(),
            'campeonato_id' => Campeonato::factory(),
            'nome' => $this->faker->company,
            // 'sigla' => $this->faker->lexify('???'), // Removido
            // 'tecnico' => $this->faker->name, // Removido
            'status_inscricao' => 'aprovada',
        ];
    }
}
