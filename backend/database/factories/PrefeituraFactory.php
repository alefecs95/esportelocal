<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prefeitura>
 */
class PrefeituraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->city,
            'slug' => $this->faker->slug,
            'dominio' => $this->faker->domainName,
            'cor_primaria' => $this->faker->hexColor,
            'ativo' => true,
        ];
    }
}
