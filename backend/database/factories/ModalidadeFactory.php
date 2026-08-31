<?php

namespace Database\Factories;

use App\Models\Prefeitura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Modalidade>
 */
class ModalidadeFactory extends Factory
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
            'nome' => $this->faker->unique()->word,
            'tipo' => $this->faker->randomElement(['coletivo', 'individual']),
        ];
    }
}
