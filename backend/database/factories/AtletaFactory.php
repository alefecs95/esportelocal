<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Atleta>
 */
class AtletaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name,
            'cpf' => $this->faker->unique()->numerify('###########'),
            'data_nascimento' => $this->faker->date,
            'sexo' => $this->faker->randomElement(['M', 'F']),
            'nome_mae' => $this->faker->name('female'),
            'nome_pai' => $this->faker->name('male'),
            'titulo_eleitor' => $this->faker->numerify('############'),
            'zona_eleitoral' => $this->faker->numerify('###'),
            'secao_eleitoral' => $this->faker->numerify('####'),
            'cep' => $this->faker->postcode,
            'endereco' => $this->faker->streetAddress,
            'bairro' => $this->faker->citySuffix,
            'cidade' => $this->faker->city,
            'estado' => $this->faker->stateAbbr,
            'telefone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
        ];
    }
}
