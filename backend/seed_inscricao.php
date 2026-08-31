<?php

use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Inscricao;

$atleta = Atleta::where('nome', 'Benedito Valdez Galindo')->first();
if (!$atleta) {
    echo "Atleta não encontrado\n";
    exit;
}

$campCorrida = Campeonato::where('nome', 'like', '%Corrida%')->first();
if (!$campCorrida) {
    echo "Campeonato de Corrida não encontrado\n";
    exit;
}

// Remover inscrição anterior se houver (para evitar duplicação no teste)
Inscricao::where('atleta_id', $atleta->id)->where('campeonato_id', $campCorrida->id)->delete();

// Criar nova inscrição individual
try {
    Inscricao::create([
        'atleta_id' => $atleta->id,
        'campeonato_id' => $campCorrida->id,
        'prefeitura_id' => $atleta->prefeitura_id,
        'status' => 'confirmada',
        'colocacao' => rand(1, 50)
    ]);
    echo "Inscrição individual criada para {$atleta->nome} em {$campCorrida->nome}\n";
} catch (\Exception $e) {
    echo "Erro ao criar inscrição: " . $e->getMessage() . "\n";
}
