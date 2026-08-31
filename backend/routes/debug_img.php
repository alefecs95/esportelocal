<?php

use Illuminate\Support\Facades\Route;
use App\Models\Noticia;
use Illuminate\Support\Facades\Storage;

Route::get('debug-noticia-img', function () {
    $noticia = Noticia::latest()->first();
    if (!$noticia) return 'Nenhuma noticia encontrada';
    
    $path = $noticia->imagem_capa;
    $exists = Storage::disk('public')->exists($path);
    $fullPath = Storage::disk('public')->path($path);
    $url = $noticia->imagem_capa_url;
    
    return [
        'id' => $noticia->id,
        'path_db' => $path,
        'exists_public_disk' => $exists,
        'full_path_on_disk' => $fullPath,
        'url_generated' => $url,
        'app_url' => env('APP_URL'),
        'all_files_in_dir' => Storage::disk('public')->allFiles(dirname($path)),
    ];
});
