<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class TenantFileService
{
    /**
     * Retorna o ID do tenant atual ou 'global' se não houver.
     */
    protected function getTenantId(): string
    {
        if (App::has('current_tenant')) {
            $tenant = App::get('current_tenant');
            if ($tenant && isset($tenant->id)) {
                return (string) $tenant->id;
            }
        }
        return 'global';
    }

    /**
     * Gera o caminho base para o tenant atual.
     */
    public function getTenantBasePath(): string
    {
        return 'tenants/' . $this->getTenantId();
    }

    /**
     * Salva um arquivo no contexto do tenant.
     * 
     * @param UploadedFile $file O arquivo a ser salvo
     * @param string $folder A pasta dentro do contexto do tenant (ex: 'atletas/1')
     * @param string|null $tenantId ID do tenant (opcional, se não informado usa o contexto atual)
     * @param string $disk O disco de armazenamento (default: 'public')
     * @return string O caminho relativo salvo (incluindo tenant prefix)
     */
    public function storeFile(UploadedFile $file, string $folder, ?string $tenantId = null, string $disk = 'public'): string
    {
        // Se $tenantId for null, tenta pegar do contexto, senão usa 'global'
        $tenantPrefix = $tenantId ?? $this->getTenantId();
        
        // Estrutura: tenants/{id}/pasta
        // Exemplo: tenants/1/noticias
        $path = "tenants/{$tenantPrefix}/{$folder}";

        // store() gera um nome único e salva no disco
        return $file->store($path, $disk);
    }

    /**
     * Retorna a URL completa para um arquivo.
     * Útil se precisarmos gerar URLs assinadas ou customizadas no futuro.
     */
    public function getUrl(string $path): string
    {
        return Storage::url($path);
    }
    
    /**
     * Deleta um arquivo.
     */
    public function deleteFile(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }
}
