<?php

namespace Database\Factories;

use App\Models\MemoriaMarco;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MemoriaMarcoFactory extends Factory
{
    protected $model = MemoriaMarco::class;

    public function definition(): array
    {
        $titulo = 'Título municipal de ' . $this->faker->year();

        return [
            'titulo' => $titulo,
            'slug' => Str::slug($titulo) . '-' . $this->faker->unique()->numerify('###'),
            'resumo' => $this->faker->sentence(12),
            'conteudo' => $this->faker->paragraphs(2, true),
            'data_evento' => '1998-06-15',
            'ano' => 1998,
            'decada' => 1990,
            'tipo' => 'conquista',
            'destaque' => false,
            'publicado' => true,
        ];
    }
}
