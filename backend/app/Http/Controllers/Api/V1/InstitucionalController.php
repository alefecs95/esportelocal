<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Prefeitura;
use App\Services\WhatsAppGatewayService;

class InstitucionalController extends Controller
{
    public function __construct(
        private WhatsAppGatewayService $whatsAppGatewayService
    ) {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 400);
        }

        $prefeitura = Prefeitura::findOrFail($user->prefeitura_id);

        return response()->json($this->buildPrefeituraResponse($prefeitura));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 400);
        }

        $prefeitura = Prefeitura::findOrFail($user->prefeitura_id);

        $rules = [
            'nome' => 'required|string|max:255',
            'endereco' => 'nullable|string|max:500',
            'telefone' => 'nullable|string|max:20',
            'email_contato' => 'nullable|email|max:255',
            'footer_titulo' => 'nullable|string|max:255',
            'footer_link_transparencia' => 'nullable|url|max:255',
            'footer_link_editais' => 'nullable|url|max:255',
            'footer_link_contato' => 'nullable|url|max:255',
            'redes_sociais' => 'nullable|array',
            'redes_sociais.facebook' => 'nullable|url|max:255',
            'redes_sociais.instagram' => 'nullable|url|max:255',
            'redes_sociais.youtube' => 'nullable|url|max:255',
            'redes_sociais.x' => 'nullable|url|max:255',
            'cor_primaria' => 'nullable|string|max:7',
            'cor_secundaria' => 'nullable|string|max:7',
            'configuracao_estilo' => 'nullable|array',
            'integracoes' => 'nullable|array',
            'integracoes.whatsapp' => 'nullable|array',
            'integracoes.whatsapp.enabled' => 'nullable|boolean',
        ];

        // Apenas valida arquivo se for enviado um arquivo
        if ($request->hasFile('logo')) {
            $rules['logo'] = 'image|max:2048'; // Max 2MB
        }
        if ($request->hasFile('logo_rodape')) {
            $rules['logo_rodape'] = 'image|max:2048'; // Max 2MB
        }
        if ($request->hasFile('banner_portal')) {
            $rules['banner_portal'] = 'image|max:4096'; // Max 4MB
        }
        if ($request->hasFile('fundo_login')) {
            $rules['fundo_login'] = 'image|max:4096'; // Max 4MB
        }

        $data = $request->validate($rules);

        // Upload Logo
        if ($request->hasFile('logo')) {
            if ($prefeitura->logo && !filter_var($prefeitura->logo, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($prefeitura->logo)) {
                Storage::disk('public')->delete($prefeitura->logo);
            }
            $data['logo'] = $request->file('logo')->store('prefeituras/' . $prefeitura->id, 'public');
        }

        if ($request->hasFile('logo_rodape')) {
            if ($prefeitura->logo_rodape && !filter_var($prefeitura->logo_rodape, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($prefeitura->logo_rodape)) {
                Storage::disk('public')->delete($prefeitura->logo_rodape);
            }
            $data['logo_rodape'] = $request->file('logo_rodape')->store('prefeituras/' . $prefeitura->id, 'public');
        }

        // Upload Banner Portal
        if ($request->hasFile('banner_portal')) {
            if ($prefeitura->banner_portal && !filter_var($prefeitura->banner_portal, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($prefeitura->banner_portal)) {
                Storage::disk('public')->delete($prefeitura->banner_portal);
            }
            $data['banner_portal'] = $request->file('banner_portal')->store('prefeituras/' . $prefeitura->id . '/banners', 'public');
        }

        // Upload Fundo Login
        if ($request->hasFile('fundo_login')) {
            if ($prefeitura->fundo_login && !filter_var($prefeitura->fundo_login, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($prefeitura->fundo_login)) {
                Storage::disk('public')->delete($prefeitura->fundo_login);
            }
            $data['fundo_login'] = $request->file('fundo_login')->store('prefeituras/' . $prefeitura->id . '/banners', 'public');
        }

        $integracoesData = $data['integracoes'] ?? [];
        unset($data['integracoes']);
        $data['configuracoes_integracoes'] = $this->mergeIntegrationsConfig(
            $prefeitura->configuracoes_integracoes ?? [],
            $integracoesData
        );

        $prefeitura->update($data);

        return response()->json([
            'message' => 'Configurações atualizadas com sucesso',
            'data' => $this->buildPrefeituraResponse($prefeitura->fresh())
        ]);
    }

    public function testWhatsAppConnection(Request $request)
    {
        $user = $request->user();

        if (!$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 400);
        }

        $prefeitura = Prefeitura::findOrFail($user->prefeitura_id);
        app()->instance('current_tenant', $prefeitura);

        $result = $this->whatsAppGatewayService->testConnection();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function connectWhatsApp(Request $request)
    {
        $user = $request->user();

        if (!$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 400);
        }

        $prefeitura = Prefeitura::findOrFail($user->prefeitura_id);
        app()->instance('current_tenant', $prefeitura);

        $result = $this->whatsAppGatewayService->connectSession((bool) $request->boolean('reset'));

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function disconnectWhatsApp(Request $request)
    {
        $user = $request->user();

        if (!$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 400);
        }

        $prefeitura = Prefeitura::findOrFail($user->prefeitura_id);
        app()->instance('current_tenant', $prefeitura);

        $result = $this->whatsAppGatewayService->logoutSession();

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function whatsAppStatus(Request $request)
    {
        $user = $request->user();

        if (!$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 400);
        }

        $prefeitura = Prefeitura::findOrFail($user->prefeitura_id);
        app()->instance('current_tenant', $prefeitura);

        $status = $this->whatsAppGatewayService->sessionStatus();

        return response()->json([
            'ok' => (bool) ($status['ok'] ?? false),
            'enabled' => (bool) ($status['enabled'] ?? false),
            'status' => $status['connection'] ?? 'disconnected',
            'phone' => $status['phone'] ?? null,
            'qr' => $status['qr'] ?? null,
            'message' => $status['message'] ?? null,
        ]);
    }

    private function buildPrefeituraResponse(Prefeitura $prefeitura): array
    {
        $payload = $prefeitura->toArray();
        $integracoes = $prefeitura->configuracoes_integracoes ?? [];
        $whatsApp = $integracoes['whatsapp'] ?? [];

        app()->instance('current_tenant', $prefeitura);
        $session = $this->whatsAppGatewayService->sessionStatus();

        $payload['integracoes'] = [
            'whatsapp' => [
                'enabled' => (bool) ($whatsApp['enabled'] ?? false),
                'status' => $session['connection'] ?? 'disconnected',
                'phone' => $session['phone'] ?? null,
                'qr' => $session['qr'] ?? null,
                'gateway_ok' => (bool) ($session['ok'] ?? false),
            ],
        ];

        unset($payload['configuracoes_integracoes']);

        return $payload;
    }

    private function mergeIntegrationsConfig(array $current, array $incoming): array
    {
        $config = $current;
        $whatsAppCurrent = $config['whatsapp'] ?? [];
        $whatsAppIncoming = $incoming['whatsapp'] ?? [];

        if (array_key_exists('enabled', $whatsAppIncoming)) {
            $whatsAppCurrent['enabled'] = filter_var($whatsAppIncoming['enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        $config['whatsapp'] = $whatsAppCurrent;

        return $config;
    }
}
