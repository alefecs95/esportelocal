<?php

function post($url, $data) {
    $ch = curl_init($url);
    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Accept:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Ignorar verificação SSL se necessário localmente
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $httpCode, 'body' => $result];
}

$url = 'http://127.0.0.1:8000/api/v1/inscricoes';

// Teste 1: Dados vazios (esperado 422)
echo "Teste 1: Enviando dados vazios...\n";
$res = post($url, []);
echo "Status: " . $res['status'] . "\n";
echo "Body: " . $res['body'] . "\n\n";

// Teste 2: Atleta inexistente (esperado 422)
echo "Teste 2: Atleta inexistente...\n";
$res = post($url, [
    'atleta_id' => 99999,
    'campeonato_id' => 1
]);
echo "Status: " . $res['status'] . "\n";
echo "Body: " . $res['body'] . "\n\n";

// Teste 3: Campeonato inexistente (esperado 422)
echo "Teste 3: Campeonato inexistente...\n";
$res = post($url, [
    'atleta_id' => 22, 
    'campeonato_id' => 99999
]);
echo "Status: " . $res['status'] . "\n";
echo "Body: " . $res['body'] . "\n\n";

// Teste 4: Tentativa com IDs validos (ID atleta 22 e campeonato 1)
echo "Teste 4: Tentativa com IDs validos...\n";
$res = post($url, [
    'atleta_id' => 22,
    'campeonato_id' => 1
]);
echo "Status: " . $res['status'] . "\n";
echo "Body: " . $res['body'] . "\n\n";
