<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Rules\PasswordRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{


    /**
     * Obter informações do usuário autenticado
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->loadUserData(),
        ]);
    }

    /**
     * @unauthenticated
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', new PasswordRule],
            'password_reset_token' => ['required', 'string'],
        ]);

        $user = User::query()->where('recovery_token', $request->password_reset_token)
            ->where('password_reset_required', true)
            ->firstOrFail();

        if (password_verify($request->password, $user->password)) {
            return response()->json([
                'message' => 'A nova senha nao pode ser igual à atual.',
            ], Response::HTTP_CONFLICT);
        }

        $user->password = $request->password;
        $user->recovery_token = null;
        $user->password_reset_required = false;
        $user->save();

        $user->activityLogs()->create([
            'action' => 'Redefinição de senha',
            'affected_table' => 'users',
            'affected_id' => $user->id
        ]);

        return response()->json([
            'token' => $user->generateToken(),
            'user' => $user->loadUserData(),
            'message' => 'Senha alterada com sucesso',
        ]);
    }

    /**
     * Atualizar perfil do usuário
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'birth_date' => 'sometimes|nullable|date',
            'zip_code' => 'sometimes|nullable|string|max:10',
            'address' => 'sometimes|nullable|string|max:255',
            'address_number' => 'sometimes|nullable|string|max:10',
            'complement' => 'sometimes|nullable|string|max:100',
            'neighborhood' => 'sometimes|nullable|string|max:100',
            'city' => 'sometimes|nullable|string|max:100',
            'state' => 'sometimes|nullable|string|max:2',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Alterar senha do usuário
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['confirmed', new PasswordRule],
            'user_id' => ['exists:users,id'],
            'password_reset_required' => ['required_with:user_id', 'boolean'],
        ]);

        $user = $request->user();
        $password = $request->password;

        // Se vai trocar a senha de outro user
        if (isset($request->user_id)) {
            $user = User::find($request->integer('user_id'));

            Gate::authorize('update:user', $user);
        }

        // Se o usuario tiver que trocar a senha ao logar
        $user->password_reset_required = $request->boolean('password_reset_required');

        // Se não tiver passado a senha, gera uma e retorna
        if (!isset($request->password)) {
            $generatedPassword = Str::random(10);
            $password = $generatedPassword;
        }

        $user->password = $password;
        $user->revokeTokens();

        DB::beginTransaction();
        try {

            $request->user()->activityLogs()->create([
                'action' => 'Alteração de Senha',
                'description' => 'Senha de ' . $user->full_name . ' alterada.',
                'affected_table' => 'users',
                'affected_id' => $user->id,
            ]);

            $user->save();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);

            return response()->json([
                'message' => 'Erro alterar senha',
                'error' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Senha alterada com sucesso!',
            'generated_password' => isset($generatedPassword) ? $generatedPassword : false,
        ]);
    }

    /**
     * Alterar status do usuário
     */
    public function changeStatus(Request $request, User $user): JsonResponse
    {
        Gate::authorize('update:user', $user);

        $request->validate([
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);

        $loggedUser = $request->user();
        if ($user->id == $loggedUser->id) {
            return response()->json(['message' => 'Não pode alterar o próprio status'], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $user->status = $request->status;
            $user->save();

            $loggedUser->activityLogs()->create([
                'action' => 'Alteração de Status',
                'description' => 'Status de ' . $user->full_name . ' alterado.',
                'affected_table' => 'users',
                'affected_id' => $user->id
            ]);

            if ($request->status == UserStatus::EXCLUDED) {
                $user->revokeTokens();
            }

            DB::commit();

            return response()->json([
                'message' => 'Status alterado com sucesso!',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);

            return response()->json([
                'message' => 'Erro alterar status',
                'error' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
