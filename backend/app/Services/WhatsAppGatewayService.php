<?php

namespace App\Services;

use App\Models\Prefeitura;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppGatewayService
{
    public function sendText(string $phone, string $message): bool
    {
        [$baseUrl, $deviceToken, $enabled] = $this->resolveConfig();

        if (!$enabled || !$baseUrl || !$deviceToken) {
            Log::warning('WhatsApp não configurado. Mensagem não enviada.', [
                'phone' => $phone,
            ]);
            return false;
        }

        $url = $baseUrl . '/send/text';

        $response = Http::timeout(15)
            ->withHeaders([
                'token' => $deviceToken,
                'Accept' => 'application/json',
            ])
            ->post($url, [
                'number' => $phone,
                'text' => $message,
            ]);

        if (!$response->successful()) {
            Log::error('Falha ao enviar mensagem WhatsApp', [
                'phone' => $phone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    public function testConnection(): array
    {
        [$baseUrl, $deviceToken, $enabled] = $this->resolveConfig();

        if (!$enabled) {
            return [
                'ok' => false,
                'message' => 'A integração com WhatsApp está desativada.',
            ];
        }

        if (!$baseUrl || !$deviceToken) {
            return [
                'ok' => false,
                'message' => 'Informe Base URL e o token do dispositivo para testar a conexão.',
            ];
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'token' => $deviceToken,
                    'Accept' => 'application/json',
                ])
                ->get($baseUrl);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Conexão com WhatsApp estabelecida com sucesso.',
                    'status' => $response->status(),
                ];
            }

            if (in_array($response->status(), [401, 403], true)) {
                return [
                    'ok' => false,
                    'message' => 'Conectou ao serviço, mas o token foi rejeitado. Verifique se você usou o token do dispositivo e não a API Key.',
                    'status' => $response->status(),
                ];
            }

            return [
                'ok' => false,
                'message' => 'Não foi possível validar a conexão. Verifique Base URL.',
                'status' => $response->status(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha de conexão com o serviço WhatsApp.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function resolveConfig(): array
    {
        $baseUrl = null;
        $deviceToken = null;
        $enabled = false;

        if (App::has('current_tenant')) {
            $tenant = App::get('current_tenant');
            if (!$tenant instanceof Prefeitura && isset($tenant->id)) {
                $tenant = Prefeitura::find($tenant->id);
            }

            $integracoes = $tenant?->configuracoes_integracoes ?? [];
            $whatsApp = $integracoes['whatsapp'] ?? [];
            $baseUrl = $whatsApp['base_url'] ?? null;
            $deviceToken = $whatsApp['device_token'] ?? null;
            $enabled = (bool) ($whatsApp['enabled'] ?? false);
        }

        $resolvedBaseUrl = trim((string) $baseUrl);
        $resolvedBaseUrl = trim($resolvedBaseUrl, " \t\n\r\0\x0B`'\"");
        $resolvedBaseUrl = rtrim($resolvedBaseUrl, '/');

        $resolvedDeviceToken = trim((string) $deviceToken);
        $resolvedDeviceToken = trim($resolvedDeviceToken, " \t\n\r\0\x0B`'\"");

        return [$resolvedBaseUrl, $resolvedDeviceToken, $enabled];
    }
}
