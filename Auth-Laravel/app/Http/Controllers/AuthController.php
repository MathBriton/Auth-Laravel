<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Login do usuário
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('cpf', $request->cpf)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'message' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Verificar se o usuário está ativo
        if ($user->status !== UserStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'message' => ['Sua conta não está ativa. Entre em contato com o administrador.'],
            ]);
        }

        DB::beginTransaction();

        try {
            if ($user->password_reset_required) {
                $user->activityLogs()->create([
                    'action' => 'Login',
                    'description' => 'Realizou login. Alteração de senha necessária',
                    'affected_table' => 'users',
                    'affected_id' => $user->id
                ]);

                $user->recovery_token = $user->generateToken();
                $user->revokeTokens();
                $user->save();

                DB::commit();

                return response()->json([
                    'token' => $user->recovery_token,
                    'password_reset_required' => true,
                ]);
            }

            $token = $user->generateToken();

            $user->activityLogs()->create([
                'action' => 'Login',
                'description' => 'Realizou login.',
                'affected_table' => 'users',
                'affected_id' => $user->id
            ]);

            $user->last_access_at = now();
            $user->save();

            DB::commit();

            return response()->json([
                'token' => $token,
                'user' => $user->loadUserData(),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Erro ao logar: ' . $th->getMessage());

            return response()->json([
                'message' => 'Erro ao logar, tente novamente',
                'error' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
