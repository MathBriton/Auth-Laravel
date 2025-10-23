<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Enums\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $loggedUser = $request->user();

        $request->validate([
            'type' => ['required', Rule::enum(UserType::class)],
        ]);

        // Pegando permissões padrão para o tipo de usuário que foi passado
        $permissions = $loggedUser->permissions()->whereIn('id', Permission::permissionsForUserType($request->type))->get();

        return response()->json($permissions);
    }

    /**
     * Retorna lista de permissões de um usuário
     * Porém não pode ver permissoes que não possui
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $permissions = $user->permissions()
            ->whereIn('permission_id', $user->permissions()->pluck('permission_id'))
            ->select('permissions.*', DB::raw('permission_user.enabled'))
            ->get();

        return response()->json($permissions);
    }


    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permission_id' => ['required', 'exists:permissions,id'],
            'enabled' => ['required', 'boolean'],
        ]);

        if ($user->id == $request->user()->id) {
            return response()->json(['message' => 'Não é possível alterar permissões da própria conta'], Response::HTTP_FORBIDDEN);
        }

        Gate::authorize('update:user', $user);

        if (!$user->permissions->contains($request->permission_id)) {
            return response()->json(['message' => 'Este usuário não possui esta permissão.'], Response::HTTP_NOT_FOUND);
        }

        $permission = Permission::whereId($request->permission_id)->firstOrFail();

        DB::beginTransaction();

        try {
            $statusText = $request->enabled ? 'ativada' : 'desativada';

            // Atualiza o pivot com sync para garantir a atualização
            $user->permissions()->syncWithoutDetaching([
                $request->permission_id => ['enabled' => $request->enabled],
            ]);

            $request->user()->activityLogs()->create([
                'action' => 'Alteração de Permissão',
                'description' => "Permissão '{$permission->name}' do usuário $user->full_name foi $statusText.",
                'affected_table' => 'users',
                'affected_id' => $user->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Permissão alterada.']);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);

            return response()->json(['message' => 'Erro ao alterar permissão', 'error' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
