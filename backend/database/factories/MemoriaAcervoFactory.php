<?php

namespace Database\Factories;

use App\Models\MemoriaAcervo;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemoriaAcervoFactory extends Factory
{
    protected $model = MemoriaAcervo::class;

    public function definition(): array
    {
        return [
            'tipo' => 'foto',
            'titulo' => 'Foto histórica',
            'descricao' => 'Registro do acervo municipal.',
            'arquivo' => 'tenants/1/memoria/foto.jpg',
            'mime' => 'image/jpeg',
            'ano' => 1998,
            'decada' => 1990,
            'origem' => 'curadoria',
            'status' => 'aprovado',
            'publicado' => true,
        ];
    }
}
