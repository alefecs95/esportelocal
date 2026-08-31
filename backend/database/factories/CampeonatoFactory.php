<?php

namespace Database\Factories;

use App\Models\Modalidade;
use App\Models\Prefeitura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campeonato>
 */
class CampeonatoFactory extends Factory
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
            'nome' => $this->faker->sentence(3),
            'modalidade_id' => Modalidade::factory(),
            'categoria' => 'Adulto',
            'data_inicio' => $this->faker->date(),
            'data_fim' => $this->faker->date(),
            'tipo_competicao' => 'equipe',
            'status' => 'draft',
            'genero' => 'masculino',
            // 'descricao' => $this->faker->paragraph, // Campo removido ou não existe
        ];
    }
}
