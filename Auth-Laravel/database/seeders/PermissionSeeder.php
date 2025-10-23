<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $resources = [
            'activity_log' => [
                'list' => 'Listar Logs de Atividade',
                'create' => 'Criar Log de Atividade',
                'update' => 'Atualizar Log de Atividade',
                'delete' => 'Deletar Log de Atividade',
            ],
            'user' => [
                'list' => 'Listar Usuários',
                'create' => 'Criar Usuário',
                'update' => 'Atualizar Usuário',
                'delete' => 'Deletar Usuário',
            ],
            'permission' => [
                'list' => 'Listar Permissões',
                'create' => 'Criar Permissão',
                'update' => 'Atualizar Permissão',
                'delete' => 'Deletar Permissão',
            ],
        ];

        $permissions = [];
        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action => $name) {
                $permissions[] = [
                    'action' => $action,
                    'resource' => $resource,
                    'name' => $name,
                ];
            }
        }

        $uniqueKeys = ['action', 'resource'];
        $updateColumns = ['name'];

        Permission::upsert($permissions, $uniqueKeys, $updateColumns);

        // Sincroniza permissões para usuários já existentes
        $this->syncPermissionsForAllUsers();

        $this->command->info('Permissões criadas com sucesso!');
    }

    /**
     * Sincroniza permissões para todos os usuários com base no tipo de usuário
     * Isso é necessário pois caso uma permissão seja adicionada, ela não será automaticamente
     * atribuída aos usuários já existentes
     */
    private function syncPermissionsForAllUsers(): void
    {
        // Carrega permissões para cada tipo de usuário
        $permissionsByType = [
            UserType::MASTER->value => Permission::permissionsForUserType(UserType::MASTER),
            UserType::ADMIN->value => Permission::permissionsForUserType(UserType::ADMIN),
            UserType::MEMBER->value => Permission::permissionsForUserType(UserType::MEMBER)
        ];

        User::chunk(300, function ($users) use ($permissionsByType) {
            DB::beginTransaction();

            try {
                $this->processUserChunkPermissions($users, $permissionsByType);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erro ao sincronizar permissões para usuários: ' . $e->getMessage());
            }
        });
    }

    private function processUserChunkPermissions($users, $permissionsByType)
    {
        foreach ($users as $user) {
            // Se tipo de usuário não existe no enum, pula este usuário
            if (UserType::tryFrom($user->getRawOriginal('type')) === null) {
                continue;
            }

            // Determina o conjunto correto de permissões para este usuário
            $typeKey = $user->type->value;

            $typePermissionIds = $permissionsByType[$typeKey] ?? [];

            // Se não há permissões para este tipo, continua o loop
            if (empty($typePermissionIds)) {
                continue;
            }

            // Pega permissões atuais do usuário com seu status de habilitado
            $currentPermissions = DB::table('permission_user')
                ->where('user_id', $user->id)
                ->pluck('enabled', 'permission_id')
                ->toArray();

            // Prepara dados para sincronização
            $syncData = [];
            foreach ($typePermissionIds as $permissionId) {
                // Se permissão já existe, mantém seu status de habilitado
                // Caso contrário, define habilitado como true para novas permissões
                $syncData[$permissionId] = [
                    'enabled' => $currentPermissions[$permissionId] ?? true
                ];
            }

            // Sincroniza permissões sem deletar as existentes
            $user->permissions()->syncWithoutDetaching($syncData);
        }
    }
}
