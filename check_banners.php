<?php
require 'backend/vendor/autoload.php';
$app = require 'backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$banners = \App\Models\Campeonato::select('id', 'nome', 'banner')->limit(5)->get();
foreach ($banners as $c) {
    echo "ID: " . $c->id . " | Nome: " . $c->nome . " | Banner: " . var_export($c->banner, true) . " | banner_url: " . var_export($c->banner_url, true) . "\n";
}
