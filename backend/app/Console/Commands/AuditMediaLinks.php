<?php

namespace App\Console\Commands;

use App\Models\Atleta;
use App\Models\AtletaArquivo;
use App\Models\DiretoriaMembro;
use App\Models\Entidade;
use App\Models\Equipe;
use App\Models\Noticia;
use App\Models\Prefeitura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AuditMediaLinks extends Command
{
    protected $signature = 'media:audit-links {--limit=100} {--http} {--disk-only}';
    protected $description = 'Audita paths e URLs de mídias salvas no backend';

    protected array $rows = [];
    protected int $missingFiles = 0;
    protected int $invalidUrls = 0;
    protected int $checked = 0;

    public function handle()
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $checkHttp = (bool) $this->option('http');
        $diskOnly = (bool) $this->option('disk-only');

        $this->info('Iniciando auditoria de mídia...');
        $this->line('APP_URL: ' . (string) config('app.url'));
        $this->line('Disk public root: ' . storage_path('app/public'));
        $this->newLine();

        $this->auditDiskFiles($limit, $checkHttp);

        if (!$diskOnly) {
            try {
                $this->auditPrefeituras($limit, $checkHttp);
                $this->auditNoticias($limit, $checkHttp);
                $this->auditDiretoria($limit, $checkHttp);
                $this->auditAtletas($limit, $checkHttp);
                $this->auditEquipes($limit, $checkHttp);
                $this->auditEntidades($limit, $checkHttp);
                $this->auditArquivosAtleta($limit, $checkHttp);
            } catch (\Throwable $e) {
                $this->warn('Não foi possível auditar banco de dados: ' . $e->getMessage());
                $this->warn('Use --disk-only para auditar apenas os arquivos físicos.');
            }
        }

        if (count($this->rows) > 0) {
            $this->table(
                ['origem', 'id', 'campo', 'path', 'arquivo', 'url_http'],
                $this->rows
            );
        } else {
            $this->warn('Nenhum registro de mídia encontrado.');
        }

        $this->newLine();
        $this->info('Resumo');
        $this->line('Itens auditados: ' . $this->checked);
        $this->line('Arquivos faltando no disk public: ' . $this->missingFiles);
        $this->line('URLs com falha HTTP: ' . $this->invalidUrls);

        if ($this->missingFiles > 0 || $this->invalidUrls > 0) {
            $this->error('Foram encontrados problemas de mídia.');
            return self::FAILURE;
        }

        $this->info('Auditoria concluída sem problemas.');
        return self::SUCCESS;
    }

    protected function auditPrefeituras(int $limit, bool $checkHttp): void
    {
        $items = Prefeitura::withTrashed()->orderByDesc('id')->limit($limit)->get();
        foreach ($items as $item) {
            $this->checkMedia('prefeitura', (string) $item->id, 'logo', $item->logo, $item->logo_url, $checkHttp);
            $this->checkMedia('prefeitura', (string) $item->id, 'banner_portal', $item->banner_portal, $item->banner_portal_url, $checkHttp);
            $this->checkMedia('prefeitura', (string) $item->id, 'fundo_login', $item->fundo_login, $item->fundo_login_url, $checkHttp);
        }
    }

    protected function auditNoticias(int $limit, bool $checkHttp): void
    {
        $items = Noticia::withTrashed()->orderByDesc('id')->limit($limit)->get();
        foreach ($items as $item) {
            $this->checkMedia('noticia', (string) $item->id, 'imagem_capa', $item->imagem_capa, $item->imagem_capa_url, $checkHttp);

            $galeria = is_array($item->galeria) ? $item->galeria : [];
            $galeriaUrls = is_array($item->galeria_urls) ? $item->galeria_urls : [];
            foreach ($galeria as $idx => $path) {
                $url = $galeriaUrls[$idx] ?? null;
                $this->checkMedia('noticia', (string) $item->id, 'galeria[' . $idx . ']', $path, $url, $checkHttp);
            }

            $carrossel = is_array($item->carrossel) ? $item->carrossel : [];
            $carrosselUrls = is_array($item->carrossel_urls) ? $item->carrossel_urls : [];
            foreach ($carrossel as $idx => $path) {
                $url = $carrosselUrls[$idx] ?? null;
                $this->checkMedia('noticia', (string) $item->id, 'carrossel[' . $idx . ']', $path, $url, $checkHttp);
            }
        }
    }

    protected function auditDiretoria(int $limit, bool $checkHttp): void
    {
        $items = DiretoriaMembro::orderByDesc('id')->limit($limit)->get();
        foreach ($items as $item) {
            $this->checkMedia('diretoria', (string) $item->id, 'foto', $item->foto, $item->foto_url, $checkHttp);
        }
    }

    protected function auditAtletas(int $limit, bool $checkHttp): void
    {
        $items = Atleta::withTrashed()->orderByDesc('id')->limit($limit)->get();
        foreach ($items as $item) {
            $this->checkMedia('atleta', (string) $item->id, 'foto', $item->foto, $item->foto_url, $checkHttp);
        }
    }

    protected function auditEquipes(int $limit, bool $checkHttp): void
    {
        $items = Equipe::withTrashed()->orderByDesc('id')->limit($limit)->get();
        foreach ($items as $item) {
            $this->checkMedia('equipe', (string) $item->id, 'logo', $item->logo, $item->logo_url, $checkHttp);
        }
    }

    protected function auditEntidades(int $limit, bool $checkHttp): void
    {
        $items = Entidade::withTrashed()->orderByDesc('id')->limit($limit)->get();
        foreach ($items as $item) {
            $this->checkMedia('entidade', (string) $item->id, 'logo', $item->logo, $item->logo_url, $checkHttp);
        }
    }

    protected function auditArquivosAtleta(int $limit, bool $checkHttp): void
    {
        $items = AtletaArquivo::orderByDesc('id')->limit($limit)->get();
        foreach ($items as $item) {
            $this->checkMedia('atleta_arquivo', (string) $item->id, 'path', $item->path, $item->url, $checkHttp);
        }
    }

    protected function checkMedia(string $source, string $id, string $field, ?string $path, ?string $url, bool $checkHttp): void
    {
        if (!$path) {
            return;
        }

        $this->checked++;

        $isUrlPath = filter_var($path, FILTER_VALIDATE_URL);
        $fileStatus = 'ok';

        if (!$isUrlPath) {
            $normalizedPath = ltrim($path, '/');
            if (!Storage::disk('public')->exists($normalizedPath)) {
                $fileStatus = 'faltando';
                $this->missingFiles++;
            }
        } else {
            $fileStatus = 'externo';
        }

        $httpStatus = $checkHttp ? $this->checkHttpStatus($url) : 'skip';
        if ($checkHttp && $httpStatus !== 'ok') {
            $this->invalidUrls++;
        }

        if ($fileStatus !== 'ok' || ($checkHttp && $httpStatus !== 'ok')) {
            $this->rows[] = [
                'origem' => $source,
                'id' => $id,
                'campo' => $field,
                'path' => $path,
                'arquivo' => $fileStatus,
                'url_http' => $httpStatus,
            ];
        }
    }

    protected function auditDiskFiles(int $limit, bool $checkHttp): void
    {
        $allFiles = Storage::disk('public')->allFiles();
        $files = array_slice($allFiles, 0, $limit);

        foreach ($files as $idx => $path) {
            $url = $this->buildMediaUrl($path);
            $this->checked++;

            $httpStatus = $checkHttp ? $this->checkHttpStatus($url) : 'skip';
            if ($checkHttp && $httpStatus !== 'ok') {
                $this->invalidUrls++;
                $this->rows[] = [
                    'origem' => 'disk_public',
                    'id' => (string) ($idx + 1),
                    'campo' => 'arquivo',
                    'path' => $path,
                    'arquivo' => 'ok',
                    'url_http' => $httpStatus,
                ];
            }
        }
    }

    protected function buildMediaUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '') {
            $baseUrl = rtrim(url('/'), '/');
        }

        $basePath = rtrim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
        $baseHasBackendPublic = $basePath !== '' && str_ends_with($basePath, '/backend/public');

        if (app()->isLocal() || $baseHasBackendPublic) {
            $mediaBasePath = '/api/v1/public/media';
        } else {
            $mediaBasePath = '/backend/public/api/v1/public/media';
        }

        $normalizedPath = ltrim($path, '/');
        $encodedPath = str_replace('%2F', '/', rawurlencode($normalizedPath));

        return $baseUrl . $mediaBasePath . '/' . $encodedPath;
    }

    protected function checkHttpStatus(?string $url): string
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return 'url_invalida';
        }

        try {
            $response = Http::timeout(10)->withOptions(['verify' => false])->get($url);
            return $response->successful() ? 'ok' : 'http_' . $response->status();
        } catch (\Throwable) {
            return 'erro_http';
        }
    }
}
