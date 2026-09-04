<?php

namespace Database\Factories;

use App\Models\MemoriaPersonalidade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MemoriaPersonalidadeFactory extends Factory
{
    protected $model = MemoriaPersonalidade::class;

    public function definition(): array
    {
        $nome = $this->faker->name();

        return [
            'nome' => $nome,
            'slug' => Str::slug($nome) . '-' . $this->faker->unique()->numerify('###'),
            'papel' => 'atleta',
            'mini_bio' => $this->faker->paragraph(),
            'destaque' => false,
            'publicado' => true,
        ];
    }
}
