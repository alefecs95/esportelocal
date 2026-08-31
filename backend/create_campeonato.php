<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campeonato;
use App\Models\Modalidade;

$modalidade = Modalidade::first();
if (!$modalidade) {
    $modalidade = Modalidade::create(['nome' => 'Futebol']);
}

try {
    $campeonato = Campeonato::create([
        'nome' => 'Campeonato Teste',
        'modalidade_id' => $modalidade->id,
        'categoria' => 'Adulto',
        'genero' => 'misto',
        'tipo_competicao' => 'individual',
        'data_inicio' => '2026-03-01',
        'data_fim' => '2026-03-30',
        'limite_participantes' => 10,
        'valor_inscricao' => 50.00,
        'regulamento' => 'Teste',
        'status' => 'aberto' // Importante para aparecer na lista se houver filtro
    ]);
    echo "Campeonato criado com ID: " . $campeonato->id . PHP_EOL;
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}
