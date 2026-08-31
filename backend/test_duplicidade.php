<?php

function post($url, $data) {
    $ch = curl_init($url);
    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Accept:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $httpCode, 'body' => $result];
}

$url = 'http://127.0.0.1:8000/api/v1/inscricoes';

echo "Teste 5: Duplicidade (atleta 22 ja inscrito no camp 1)...\n";
$res = post($url, [
    'atleta_id' => 22,
    'campeonato_id' => 1
]);
echo "Status: " . $res['status'] . "\n";
echo "Body: " . $res['body'] . "\n\n";
