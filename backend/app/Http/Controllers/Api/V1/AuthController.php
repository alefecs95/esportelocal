<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Atleta;
use App\Models\Entidade;
use App\Models\WhatsAppNotification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\App;

class AuthController extends Controller
{
    public function __construct(
        private WhatsAppNotificationService $whatsAppNotificationService
    ) {
    }

    public function register(Request $request)
    {
        $tenantId = App::get('current_tenant')->id ?? null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nome_clube' => 'required|string|max:255', // Novo campo
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($tenantId) {
                    return $query->where('prefeitura_id', $tenantId);
                }),
            ],
            'cpf' => [
                'required',
                'string',
                Rule::unique('users')->where(function ($query) use ($tenantId) {
                    return $query->where('prefeitura_id', $tenantId);
                }),
            ], 
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Sanitize CPF
        $validated['cpf'] = preg_replace('/[^0-9]/', '', $validated['cpf']);
        
        // Default values for self-registration
        $validated['password'] = Hash::make($validated['password']);
        $validated['perfil'] = 'RESPONSAVEL';
        $validated['status'] = 'ATIVO';
        $validated['forcar_troca_senha'] = false;

        $user = User::create($validated);

        // Criar Entidade (Clube) automaticamente
        $entidade = Entidade::create([
            'user_id' => $user->id,
            'nome' => $request->nome_clube,
            'responsavel' => $user->name,
            'prefeitura_id' => $tenantId
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'entidade' => $entidade
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        $login = $request->login;
        $password = $request->password;

        // Determine if login is email or cpf
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'cpf';
        
        // If it's CPF, we might need to sanitize it (remove non-digits)
        if ($field === 'cpf') {
            $login = preg_replace('/[^0-9]/', '', $login);
        }

        $user = User::where($field, $login)->first();
        
        // Se não achou usuário no escopo do tenant atual, tenta buscar globalmente APENAS se for SUPER_ADMIN
        // Isso é necessário porque o TenantScope esconde usuários de outros tenants (inclusive o Super Admin se ele estiver em outro tenant)
        if (!$user) {
            // Remove o escopo global temporariamente para buscar o usuário
            $userGlobal = User::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where($field, $login)
                ->first();

            if ($userGlobal && $userGlobal->perfil === 'SUPER_ADMIN') {
                $user = $userGlobal;
            }
        }

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.'
            ], 401);
        }

        if ($user->status !== 'ativo' && $user->status !== 'ATIVO') {
            return response()->json([
                'message' => 'Usuário inativo.'
            ], 403);
        }

        // Revoke all tokens... or just create a new one? Let's just create one.
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Ensure computed properties are appended
        $user->append(['is_manager', 'is_atleta']);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
    
    public function me(Request $request)
    {
        $user = $request->user();
        $user->append(['is_manager', 'is_atleta']);
        return $user;
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->forcar_troca_senha = false;
        $user->save();

        return response()->json([
            'message' => 'Senha atualizada com sucesso.'
        ]);
    }

    public function forgotPasswordWhatsApp(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
        ]);

        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'cpf';
        if ($field === 'cpf') {
            $login = preg_replace('/[^0-9]/', '', $login);
        }

        $user = User::where($field, $login)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Se os dados estiverem corretos, enviaremos a recuperação via WhatsApp.'
            ]);
        }

        $atleta = Atleta::where('cpf', preg_replace('/[^0-9]/', '', (string) $user->cpf))->first();
        $phone = $atleta?->telefone;
        if (!$phone) {
            $phone = WhatsAppNotification::where('user_id', $user->id)->value('phone');
        }
        if (!$phone) {
            return response()->json([
                'message' => 'Usuário sem telefone válido para recuperação via WhatsApp.'
            ], 422);
        }

        $token = $this->whatsAppNotificationService->createPasswordResetToken($user);
        $frontendUrl = $this->whatsAppNotificationService->getTenantFrontendUrl();
        $resetLink = $frontendUrl . '/login/reset/' . $token;

        $this->whatsAppNotificationService->notifyPasswordRecovery($user, $phone, $resetLink);

        return response()->json([
            'message' => 'Se os dados estiverem corretos, enviaremos a recuperação via WhatsApp.'
        ]);
    }

    public function resetPasswordByToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $resetToken = $this->whatsAppNotificationService->consumePasswordResetToken($request->input('token'));
        if (!$resetToken) {
            return response()->json([
                'message' => 'Token inválido ou expirado.'
            ], 422);
        }

        $user = User::find($resetToken->user_id);
        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        $user->password = Hash::make($request->input('password'));
        $user->forcar_troca_senha = false;
        $user->save();

        return response()->json([
            'message' => 'Senha redefinida com sucesso.'
        ]);
    }
}
