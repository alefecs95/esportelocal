<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Noticia;
use Illuminate\Support\Facades\Storage;

class DebugNewsImage extends Command
{
    protected $signature = 'debug:news-image';
    protected $description = 'Debug news image path';

    public function handle()
    {
        $noticia = Noticia::latest()->first();
        if (!$noticia) {
            $this->error('Nenhuma noticia encontrada');
            return;
        }
        
        $path = $noticia->imagem_capa;
        $exists = Storage::disk('public')->exists($path);
        $fullPath = Storage::disk('public')->path($path);
        $url = $noticia->imagem_capa_url;
        
        $this->info("ID: {$noticia->id}");
        $this->info("DB Path: {$path}");
        $this->info("Exists (public disk): " . ($exists ? 'YES' : 'NO'));
        $this->info("Full Path: {$fullPath}");
        $this->info("URL: {$url}");
        $this->info("APP_URL: " . env('APP_URL'));
        
        $dir = dirname($path);
        $this->info("Checking dir: {$dir}");
        if (Storage::disk('public')->exists($dir)) {
            $files = Storage::disk('public')->allFiles($dir);
            $this->info("Files in dir: " . implode(', ', $files));
        } else {
            $this->error("Directory does not exist on public disk!");
        }
    }
}
