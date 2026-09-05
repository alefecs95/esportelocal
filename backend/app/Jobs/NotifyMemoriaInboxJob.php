<?php

namespace App\Jobs;

use App\Models\Prefeitura;
use App\Services\MemoriaCurationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class NotifyMemoriaInboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $prefeituraId,
        public string $titulo,
        public string $mensagem,
        public string $tipo = 'envio'
    ) {
    }

    public function handle(MemoriaCurationService $curation): void
    {
        $tenant = Prefeitura::query()->withoutGlobalScopes()->find($this->prefeituraId);
        if ($tenant) {
            App::instance('current_tenant', $tenant);
        }

        $curation->notifyInbox($this->titulo, $this->mensagem, $this->tipo);
    }
}
