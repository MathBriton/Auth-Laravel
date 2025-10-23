<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Permission
 *
 * @property int $id
 * @property string $resource
 * @property string $action
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 **/
class Permission extends Model
{
    protected $fillable = [
        'action',
        'resource',
        'name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions');
    }


    /**
     * @return array<int>
     */
    public static function permissionsForUserType(UserType $userType)
    {
        if ($userType === UserType::MASTER) {
            return Permission::all()->pluck('id')->toArray();
        }

        $adminPermissions = [
            'activity_log' => ['list', 'create', 'update', 'delete'],
            'user' => ['list', 'create', 'update', 'delete'],
            'permission' => ['list', 'create', 'update', 'delete'],
        ];

        $memberPermissions = [];

        $permissions = [];
        switch ($userType) {
            case UserType::ADMIN:
                $permissions = $adminPermissions;
                break;
            case UserType::MEMBER:
                $permissions = $memberPermissions;
                break;
            default:
                throw new \Exception('Tipo de usuário inválido');
        }

        $query = Permission::query();

        foreach ($permissions as $resource => $actions) {
            $query->orWhere(function ($q) use ($resource, $actions) {
                $q->where('resource', $resource);
                $q->whereIn('action', $actions);
            });
        }

        $filteredPermissions = $query->get()->pluck('id')->toArray();

        return $filteredPermissions;
    }
}
