<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppNotification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;

class WhatsAppNotificationController extends Controller
{
    public function __construct(
        private WhatsAppNotificationService $whatsAppNotificationService
    ) {
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'type' => 'required|string|max:80',
            'message' => 'required|string|max:4000',
            'user_id' => 'nullable|integer|exists:users,id',
            'force' => 'nullable|boolean',
        ]);

        $phone = $this->whatsAppNotificationService->normalizePhone($validated['phone']);
        if (!$phone) {
            return response()->json(['message' => 'Telefone inválido.'], 422);
        }

        $this->whatsAppNotificationService->dispatchMessage(
            phone: $phone,
            type: $validated['type'],
            message: $validated['message'],
            userId: $validated['user_id'] ?? null,
            force: (bool) ($validated['force'] ?? false),
        );

        return response()->json(['message' => 'Notificação enviada para fila de processamento.']);
    }

    public function unsubscribe(string $token)
    {
        $notification = WhatsAppNotification::where('unsubscribe_token', $token)->first();
        if (!$notification) {
            return response()->json(['message' => 'Token de descadastro inválido.'], 404);
        }

        $notification->receive_notifications = false;
        $notification->save();

        return response()->json(['message' => 'Notificações desativadas com sucesso.']);
    }

    public function updatePreference(Request $request)
    {
        $validated = $request->validate([
            'receive_notifications' => 'required|boolean',
        ]);

        $user = $request->user();
        $notification = WhatsAppNotification::where('user_id', $user->id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Preferência de WhatsApp não encontrada para o usuário.'], 404);
        }

        $notification->receive_notifications = $validated['receive_notifications'];
        $notification->save();

        return response()->json($notification);
    }
}
