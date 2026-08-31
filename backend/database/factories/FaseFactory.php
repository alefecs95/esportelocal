<?php

namespace Database\Factories;

use App\Models\Campeonato;
use App\Models\Prefeitura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fase>
 */
class FaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'prefeitura_id' => Prefeitura::factory(), // Removido
            'campeonato_id' => Campeonato::factory(),
            'nome' => $this->faker->word,
            'ordem' => 1,
            'tipo' => 'custom',
            'status' => 'planejada',
            // 'mata_mata' => false, // Removido
            // 'ida_e_volta' => false, // Removido
        ];
    }
}
